<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Throwable;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition */
abstract class FileIteratingPreconditionUnitTestCase extends PreconditionUnitTestCase
{
    // Override in subclasses.
    protected const NAME = 'NAME';
    protected const DESCRIPTION = 'DESCRIPTION';
    protected const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

    protected EnvironmentInterface|ObjectProphecy $environment;
    protected FileFinderInterface|ObjectProphecy $fileFinder;
    protected FilesystemInterface|ObjectProphecy $filesystem;
    protected PathFactoryInterface|ObjectProphecy $pathFactory;
    protected RecursiveIteratorIterator $fakeIterator;

    protected function setUp(): void
    {
        $this->fakeIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator([]));
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn($this->fakeIterator);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::type(PathInterface::class))
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    /** @covers ::exitEarly */
    public function testExitEarly(): void
    {
        $this->filesystem
            ->fileExists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();

        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $pathListFactory = self::createPathListFactory();
        $translatableFactory = self::createTranslatableFactory();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        $sut = new class ($environment, $fileFinder, $filesystem, $pathFactory, $pathListFactory, $translatableFactory) extends AbstractFileIteratingPrecondition
        {
            protected const NAME = 'NAME';
            protected const DESCRIPTION = 'DESCRIPTION';
            protected const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

            protected function assertIsSupportedFile(
                string $codebaseName,
                PathInterface $codebaseRoot,
                PathInterface $file,
            ): void {
                // Always pass.
            }

            protected function exitEarly(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions,
            ): bool {
                return true;
            }

            public function getName(): TranslatableInterface
            {
                return $this->t(static::NAME);
            }

            public function getDescription(): TranslatableInterface
            {
                return $this->t(static::DESCRIPTION);
            }

            protected function getFulfilledStatusMessage(): TranslatableInterface
            {
                return $this->t(static::FULFILLED_STATUS_MESSAGE);
            }
        };

        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
    }

    /** @covers ::doAssertIsFulfilled */
    public function testActiveDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->filesystem
            ->fileExists($activeDirPath)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testStagingDirectoryDoesNotExistCountsAsFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->filesystem
            ->fileExists($stagingDirPath)
            ->willReturn(false);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated non-existent directories as fulfilled.');
    }

    /** @covers ::doAssertIsFulfilled */
    public function testNoFilesFound(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willReturn([]);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);
        $statusMessage = $sut->getStatusMessage($activeDirPath, $stagingDirPath);

        $this->assertFulfilled($isFulfilled, $statusMessage, 'Treated empty codebase as fulfilled.');
    }

    /**
     * @covers ::doAssertIsFulfilled
     *
     * @dataProvider providerFileFinderExceptions
     */
    public function testFileFinderExceptions(ExceptionInterface $previous): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->fileFinder
            ->find(Argument::type(PathInterface::class), Argument::type(PathListInterface::class))
            ->willThrow($previous);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertFalse($isFulfilled);

        try {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        } catch (Throwable $e) {
            self::assertInstanceOf(PreconditionException::class, $e);
            self::assertSame($e->getMessage(), $previous->getMessage());
            self::assertInstanceOf($previous::class, $e->getPrevious());
        }
    }

    public function providerFileFinderExceptions(): array
    {
        return [
            'InvalidArgumentException' => [new InvalidArgumentException(self::createTranslatableMessage('Exclusions include invalid paths.'))],
            'IOException' => [new IOException(self::createTranslatableMessage('The directory cannot be found or is not actually a directory.'))],
        ];
    }

    /** @covers ::getFulfilledStatusMessage */
    public function assertFulfilled(
        bool $isFulfilled,
        TranslatableInterface $statusMessage,
        string $assertionMessage,
    ): void {
        self::assertTrue($isFulfilled, $assertionMessage);
        self::assertEquals(static::FULFILLED_STATUS_MESSAGE, $statusMessage->trans(), 'Got correct status message');
    }
}
