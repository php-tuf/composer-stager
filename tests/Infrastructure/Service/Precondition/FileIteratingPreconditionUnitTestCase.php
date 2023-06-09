<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Domain\Value\Translation\TranslatableInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Infrastructure\Service\Translation\TestTranslator;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Translation\TestTranslatableMessage;
use Prophecy\Argument;
use Throwable;

/**
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 */
abstract class FileIteratingPreconditionUnitTestCase extends PreconditionTestCase
{
    abstract protected function fulfilledStatusMessage(): string;

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
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    /** @covers \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition::exitEarly */
    public function testExitEarly(): void
    {
        $this->filesystem
            ->exists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        $sut = new class ($fileFinder, $filesystem, $pathFactory, $translatableFactory, $translator) extends AbstractFileIteratingPrecondition
        {
            // phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
            protected function assertIsSupportedFile(
                string $codebaseName,
                PathInterface $codebaseRoot,
                PathInterface $file,
            ): void {
            }

            protected function getFulfilledStatusMessage(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            public function getName(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            public function getDescription(): TranslatableInterface
            {
                return new TestTranslatableMessage();
            }

            protected function exitEarly(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions,
            ): bool {
                return true;
            }
        };

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
    }

    /** @covers ::assertIsFulfilled */
    public function testActiveDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $this->filesystem
            ->exists($this->activeDir)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::assertIsFulfilled */
    public function testStagingDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $this->filesystem
            ->exists($this->stagingDir)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::assertIsFulfilled */
    public function testNoFilesFound(): void
    {
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);
        $statusMessage = $sut->getStatusMessage($this->activeDir, $this->stagingDir);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::assertIsFulfilled
     *
     * @dataProvider providerFileFinderExceptions
     */
    public function testFileFinderExceptions(ExceptionInterface $previous): void
    {
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willThrow($previous);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($this->activeDir, $this->stagingDir);

        self::assertFalse($isFulfilled);

        try {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        } catch (Throwable $e) {
            self::assertInstanceOf(PreconditionException::class, $e);
            self::assertSame($e->getMessage(), $previous->getMessage());
            self::assertInstanceOf($previous::class, $e->getPrevious());
        }
    }

    public function providerFileFinderExceptions(): array
    {
        return [
            [new InvalidArgumentException(new TestTranslatableMessage('Exclusions include invalid paths.'))],
            [new IOException(new TestTranslatableMessage('The directory cannot be found or is not actually a directory.'))],
        ];
    }

    public function assertFulfilled(
        bool $isFulfilled,
        TranslatableInterface $statusMessage,
        string $assertionMessage,
    ): void {
        self::assertTrue($isFulfilled, $assertionMessage);
        self::assertEquals($this->fulfilledStatusMessage(), $statusMessage, 'Got correct status message');
    }
}
