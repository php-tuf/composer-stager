<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase
 *
 * @covers ::__construct
 * @covers ::exitEarly
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
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
final class NoSymlinksPointOutsideTheCodebaseFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointOutsideTheCodebase
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase $sut */
        $sut = $container->get(NoSymlinksPointOutsideTheCodebase::class);

        return $sut;
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerFulfilledWithValidLink
     */
    public function testFulfilledWithValidLink(string $link, string $target): void
    {
        $link = PathFactory::create($link, $this->activeDir)->resolved();
        self::ensureParentDirectory($link);
        $target = PathFactory::create($target, $this->activeDir)->resolved();
        self::ensureParentDirectory($target);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    public function providerFulfilledWithValidLink(): array
    {
        return [
            'Not in any package' => [
                'link' => 'link.txt',
                'target' => 'target.txt',
            ],
            'Pointing within a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing into a package' => [
                'link' => 'link.txt',
                'target' => 'vendor/package/target.txt',
            ],
            'Pointing out of a package' => [
                'link' => 'vendor/package/link.txt',
                'target' => 'target.txt',
            ],
            'Pointing from one package to another' => [
                'link' => 'vendor/package1/link.txt',
                'target' => 'vendor/package2/target.txt',
            ],
            'Weird relative paths' => [
                'link' => 'some/absurd/subdirectory/../with/../../a/link.txt',
                'target' => 'another/../weird/../arbitrary/target.txt',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $target = PathFactory::create($targetDir . '/target.txt')->resolved();
        $link = PathFactory::create($linkDir . '/link.txt')->resolved();
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains links that point outside the codebase, which is not supported. The first one is %s.',
            $linkDirName,
            PathFactory::create($linkDir)->resolved(),
            $link,
        );
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::ACTIVE_DIR,
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => self::TEST_WORKING_DIR,
                'linkDir' => self::STAGING_DIR,
                'linkDirName' => 'staging',
            ],
        ];
    }

    /**
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
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testWithHardLink(): void
    {
        $dirPath = self::activeDirPath();
        $link = PathFactory::create('link.txt', $dirPath)->resolved();
        $target = PathFactory::create('target.txt', $dirPath)->resolved();
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     */
    public function testWithAbsoluteLink(): void
    {
        $dirPath = self::activeDirPath();
        $link = PathFactory::create('link.txt', $dirPath)->resolved();
        $target = PathFactory::create('target.txt', $dirPath)->resolved();
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = '../';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList(...$exclusions);
        $dirPath = $this->activeDir->resolved();
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
