<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows
 *
 * @covers ::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Path\Value\PathInterface $stagingDir
 */
final class NoLinksExistOnWindowsFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoLinksExistOnWindows
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows $sut */
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
     * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
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
