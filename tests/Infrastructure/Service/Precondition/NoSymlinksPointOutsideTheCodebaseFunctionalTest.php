<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase
 *
 * @covers ::__construct
 * @covers ::exitEarly
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
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
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isFulfilled
     */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /**
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::isSupportedFile
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerFulfilledWithValidLink
     */
    public function testFulfilledWithValidLink(string $link, string $target): void
    {
        $link = PathFactory::create($link, $this->activeDir)->resolve();
        self::ensureParentDirectory($link);
        $target = PathFactory::create($target, $this->activeDir)->resolve();
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
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::isSupportedFile
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $target = PathFactory::create($targetDir . '/target.txt')->resolve();
        $link = PathFactory::create($linkDir . '/link.txt')->resolve();

        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage(sprintf(
            'The %s directory at "%s" contains links that point outside the codebase, which is not supported. The first one is "%s".',
            $linkDirName,
            PathFactory::create($linkDir)->resolve(),
            $link,
        ));

        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Rejected link pointing outside the codebase.');

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
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
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
     * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
     *
     * @dataProvider providerDirectoryDoesNotExist
     */
    public function testDirectoryDoesNotExist(string $activeDir, string $stagingDir): void
    {
        $this->doTestDirectoryDoesNotExist($activeDir, $stagingDir);
    }

    /**
     * @covers ::isFulfilled
     * @covers ::isSupportedFile
     */
    public function testWithHardLink(): void
    {
        $dirPath = PathFactory::create(self::ACTIVE_DIR);
        $link = PathFactory::create('link.txt', $dirPath)->resolve();
        $target = PathFactory::create('target.txt', $dirPath)->resolve();
        $parentDir = dirname($link);
        @mkdir($parentDir, 0777, true);
        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Ignored hard link link.');
    }

    /**
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::isSupportedFile
     * @covers ::linkPointsOutsidePath
     */
    public function testWithAbsoluteLink(): void
    {
        $dirPath = PathFactory::create(self::ACTIVE_DIR);
        $link = PathFactory::create('link.txt', $dirPath)->resolve();
        $target = PathFactory::create('target.txt', $dirPath)->resolve();
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
     * @covers ::getDefaultUnfulfilledStatusMessage
     * @covers ::isDescendant
     * @covers ::isFulfilled
     * @covers ::isSupportedFile
     * @covers ::linkPointsOutsidePath
     *
     * @dataProvider providerExclusions
     */
    public function testExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetFile = '../';
        $links = array_fill_keys($links, $targetFile);
        $exclusions = new PathList($exclusions);
        $dirPath = $this->activeDir->resolve();
        self::createSymlinks($dirPath, $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
