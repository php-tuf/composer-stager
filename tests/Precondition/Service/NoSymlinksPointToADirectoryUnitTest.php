<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait
 *
 * @property \PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 * @property \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 */
final class NoSymlinksPointToADirectoryUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected function setUp(): void
    {
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->fileSyncer = $this->prophesize(PhpFileSyncerInterface::class);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    protected function fulfilledStatusMessage(): string
    {
        return 'There are no symlinks that point to a directory.';
    }

    protected function createSut(): NoSymlinksPointToADirectory
    {
        $fileFinder = $this->fileFinder->reveal();
        $fileSyncer = $this->fileSyncer->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoSymlinksPointToADirectory($fileFinder, $fileSyncer, $filesystem, $pathFactory, $translatableFactory);
    }

    public function testExitEarlyWithRsyncFileSyncer(): void
    {
        $this->fileSyncer = $this->prophesize(RsyncFileSyncerInterface::class);
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }
}
