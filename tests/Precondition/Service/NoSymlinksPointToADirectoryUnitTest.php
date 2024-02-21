<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::assertIsSupportedFile
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 */
final class NoSymlinksPointToADirectoryUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'No symlinks point to a directory';
    protected const DESCRIPTION = 'The codebase cannot contain symlinks that point to a directory.';
    protected const FULFILLED_STATUS_MESSAGE = 'There are no symlinks that point to a directory.';

    private FileSyncerFactoryInterface|ObjectProphecy $fileSyncerFactory;
    private FileSyncerInterface|ObjectProphecy $fileSyncer;

    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->fileSyncer = $this->prophesize(PhpFileSyncerInterface::class);
        $this->fileSyncerFactory = $this->prophesize(FileSyncerFactoryInterface::class);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    protected function createSut(): NoSymlinksPointToADirectory
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $this->fileSyncerFactory
            ->create()
            ->willReturn($this->fileSyncer->reveal());
        $fileSyncerFactory = $this->fileSyncerFactory->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoSymlinksPointToADirectory($environment, $fileFinder, $fileSyncerFactory, $filesystem, $pathFactory, $translatableFactory);
    }

    public function testExitEarlyWithRsyncFileSyncer(): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();

        $this->fileSyncer = $this->prophesize(RsyncFileSyncerInterface::class);
        $this->filesystem
            ->fileExists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
    }
}
