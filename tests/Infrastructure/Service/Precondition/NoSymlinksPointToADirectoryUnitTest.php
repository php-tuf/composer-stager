<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::exitEarly
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy $fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
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
        $translator = new TestTranslator();

        return new NoSymlinksPointToADirectory($fileFinder, $fileSyncer, $filesystem, $pathFactory, $translatableFactory, $translator);
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
