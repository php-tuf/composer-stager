<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\API\Path\Value\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\API\Path\Value\PathInterface $stagingDir
 */
final class NoLinksExistOnWindowsFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoLinksExistOnWindows
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows $sut */
        $sut = $container->get(NoLinksExistOnWindows::class);

        return $sut;
    }

    /**
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @dataProvider providerUnfulfilled
     *
     * @group windows_only
     */
    public function testUnfulfilled(array $symlinks, array $hardLinks): void
    {
        $baseDir = self::activeDirPath();
        $link = PathFactory::create('link.txt', $baseDir)->resolved();
        $target = PathFactory::create('target.txt', $baseDir)->resolved();
        touch($target);
        self::createSymlinks($baseDir->resolved(), $symlinks);
        self::createHardlinks($baseDir->resolved(), $hardLinks);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        self::assertFalse($isFulfilled, 'Rejected link on Windows.');

        $message = sprintf(
            'The active directory at %s contains links, which is not supported on Windows. The first one is %s.',
            $baseDir->resolved(),
            $link,
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'Contains symlink' => [
                'symlinks' => ['link.txt' => 'target.txt'],
                'hardLinks' => [],
            ],
            'Contains hard link' => [
                'symlinks' => [],
                'hardLinks' => ['link.txt' => 'target.txt'],
            ],
        ];
    }

    /**
     * @covers ::exitEarly
     * @covers ::findFiles
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
     * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
     *
     * @dataProvider providerFulfilledDirectoryDoesNotExist
     */
    public function testFulfilledDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $this->doTestFulfilledDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::exitEarly
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     *
     * @group windows_only
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = 'target.txt';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = $this->activeDir->resolved();
        self::createFile($dirPath, $targetFile);
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
