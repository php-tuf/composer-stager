<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

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

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks $sut */
        $sut = $container->get(CodeBaseContainsNoSymlinks::class);

        return $sut;
    }

    /**
     * @covers ::isFulfilled
     *
     * @dataProvider providerDoesNotContainSymlinks
     */
    public function testDoesNotContainSymlinks(array $files): void
    {
        self::createFiles(self::ACTIVE_DIR, $files);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Found no symlinks.');
    }

    public function providerDoesNotContainSymlinks(): array
    {
        return [
            'Empty directory' => ['files' => []],
            'One file' => ['files' => ['file.txt']],
            'Multiple files' => [
                'files' => [
                    'one.txt',
                    'two.txt',
                    'three.txt',
                ],
            ],
            'Files with directory depth' => [
                'files' => [
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
    public function testContainsSymlinks(string $dirName, string $dirPath, string $link): void
    {
        $dirPath = PathFactory::create($dirPath)->resolve();
        $link = self::fixSeparators($link);
        self::createFile($dirPath, 'one/two/three/four/five/six.txt');
        assert(symlink(__FILE__, "{$dirPath}/{$link}"));
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Found symlinks.');
        $pattern = sprintf(
            'The %s directory at "%s" contains symlinks, which is not supported. The first one is "%s".',
            $dirName,
            $dirPath,
            self::fixSeparators("{$dirPath}/{$link}"),
        );
        self::assertSame($pattern, $statusMessage, 'Returned correct status message.');
    }

    public function providerContainsSymlinks(): array
    {
        return [
            'Active directory: root' => [
                'dirName' => 'active',
                'dirPath' => self::ACTIVE_DIR,
                'link' => 'symlink.txt',
            ],
            'Active directory: subdir' => [
                'dirName' => 'active',
                'dirPath' => self::ACTIVE_DIR,
                'link' => 'one/symlink.txt',
            ],
            'Active directory: subdir with depth' => [
                'dirName' => 'active',
                'dirPath' => self::ACTIVE_DIR,
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
            'Staging directory: root' => [
                'dirName' => 'staging',
                'dirPath' => self::STAGING_DIR,
                'link' => 'symlink.txt',
            ],
            'Staging directory: subdir' => [
                'dirName' => 'staging',
                'dirPath' => self::STAGING_DIR,
                'link' => 'one/symlink.txt',
            ],
            'Staging directory: subdir with depth' => [
                'dirName' => 'staging',
                'dirPath' => self::STAGING_DIR,
                'link' => 'one/two/three/four/five/symlink.txt',
            ],
        ];
    }

    /**
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
     *
     * @dataProvider providerDirectoryDoesNotExist
     */
    public function testDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $activeDir = PathFactory::create($activeDir);
        $stagingDir = PathFactory::create($stagingDir);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled, 'Silently ignored non-existent directory');
    }

    public function providerDirectoryDoesNotExist(): array
    {
        $nonexistentDir = self::TEST_WORKING_DIR . '/65eb69a274470dd84e9b5371f7e1e8c8';

        return [
            'Active directory' => [
                'activeDir' => $nonexistentDir,
                'stagingDir' => self::STAGING_DIR,
            ],
            'Staging directory' => [
                'activeDir' => self::ACTIVE_DIR,
                'stagingDir' => $nonexistentDir,
            ],
        ];
    }
}
