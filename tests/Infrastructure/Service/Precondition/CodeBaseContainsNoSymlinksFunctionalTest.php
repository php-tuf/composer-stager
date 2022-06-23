<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks
 *
 * @covers ::__construct
 * @covers ::findFiles
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
final class CodeBaseContainsNoSymlinksFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        self::createTestEnvironment(self::ACTIVE_DIR);
        mkdir(self::STAGING_DIR, 0777, true);

        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): CodeBaseContainsNoSymlinks
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks $fileFinder */
        $fileFinder = $container->get(CodeBaseContainsNoSymlinks::class);

        return $fileFinder;
    }

    /**
     * @covers ::isFulfilled
     *
     * @dataProvider providerDoesNotContainSymlinks
     */
    public function testDoesNotContainSymlinks($files): void
    {
        self::createFiles(self::ACTIVE_DIR, $files);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Found no symlinks.');
    }

    public function providerDoesNotContainSymlinks(): array
    {
        return [
            'Empty directory' => [[]],
            'One file' => [['file.txt']],
            'Multiple files' => [
                [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
            ],
            'Files with directory depth' => [
                [
                    'one/two.txt',
                    'three/four/five.txt',
                    'six/seven/eight/nine/ten.txt',
                ],
            ],
        ];
    }

    /**
     * @covers ::findFiles
     * @covers ::getUnfulfilledStatusMessage
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
     *
     * @dataProvider providerContainsSymlinks
     */
    public function testContainsSymlinks($directory, $source): void
    {
        $directory = PathFactory::create($directory)->resolve();
        self::createFiles($directory, ['one/two/three/four/five/six.txt']);
        self::createFile('symlink', 'target.txt');
        symlink('symlink/target.txt', "{$directory}/{$source}");
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Found symlinks.');
        self::assertSame('The codebase contains symlinks.', $statusMessage, 'Returned correct status message.');
    }

    public function providerContainsSymlinks(): array
    {
        return [
            'Active directory: root' => [
                'directory' => self::ACTIVE_DIR,
                'source' => 'symlink.txt',
            ],
            'Active directory: subdir' => [
                'directory' => self::ACTIVE_DIR,
                'source' => 'one/symlink.txt',
            ],
            'Active directory: subdir with depth' => [
                'directory' => self::ACTIVE_DIR,
                'source' => 'one/two/three/four/five/symlink.txt',
            ],
            'Staging directory: root' => [
                'directory' => self::STAGING_DIR,
                'source' => 'symlink.txt',
            ],
            'Staging directory: subdir' => [
                'directory' => self::STAGING_DIR,
                'source' => 'one/symlink.txt',
            ],
            'Staging directory: subdir with depth' => [
                'directory' => self::STAGING_DIR,
                'source' => 'one/two/three/four/five/symlink.txt',
            ],
        ];
    }
}
