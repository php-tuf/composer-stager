<?php declare(strict_types=1);

namespace PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist
 *
 * @covers ::__construct
 * @covers ::getDefaultUnfulfilledStatusMessage
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractLinkIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
 */
final class NoHardLinksExistFunctionalTest extends TestCase
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

    private function createSut(): NoHardLinksExist
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist $sut */
        $sut = $container->get(NoHardLinksExist::class);

        return $sut;
    }

    /** @covers ::isSupportedLink */
    public function testFulfilledWithNoLinks(): void
    {
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Passed with no links in the codebase.');
    }

    /** @covers ::isSupportedLink */
    public function testFulfilledWithSymlink(): void
    {
        $target = $this->activeDir->resolve() . DIRECTORY_SEPARATOR . 'target.txt';
        $link = $this->activeDir->resolve() . DIRECTORY_SEPARATOR . 'link.txt';
        touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled, 'Allowed symlink.');
    }

    /**
     * @covers ::getUnfulfilledStatusMessage
     * @covers ::isSupportedLink
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $directory, string $dirName): void
    {
        $target = PathFactory::create($directory . '/target.txt')->resolve();
        $link = PathFactory::create($directory . '/link.txt')->resolve();

        $this->expectException(PreconditionException::class);
        $this->expectExceptionMessage(sprintf(
            'The %s directory at "%s" contains hard links, which is not supported. The first one is "%s".',
            $dirName,
            PathFactory::create($directory)->resolve(),
            $link,
        ));

        touch($target);
        link($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled, 'Rejected hard link.');

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'directory' => self::ACTIVE_DIR,
                'dirName' => 'active',
            ],
            'In staging directory' => [
                'directory' => self::STAGING_DIR,
                'dirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::getDefaultUnfulfilledStatusMessage
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
