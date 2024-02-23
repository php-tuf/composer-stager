<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 *
 * @covers ::__construct
 */
final class AbstractFileIteratingPreconditionUnitTest extends FileIteratingPreconditionUnitTestCase
{
    protected const NAME = 'NAME';
    protected const DESCRIPTION = 'DESCRIPTION';
    protected const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

    protected FileFinderInterface|ObjectProphecy $fileFinder;
    protected FilesystemInterface|ObjectProphecy $filesystem;
    protected PathFactoryInterface|ObjectProphecy $pathFactory;

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
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): PreconditionInterface
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $pathListFactory = self::createPathListFactory();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        // Create a concrete implementation for testing since the SUT in
        // this case, being abstract, can't be instantiated directly.
        return new class (
            $environment,
            $fileFinder,
            $filesystem,
            $pathFactory,
            $pathListFactory,
            $translatableFactory,
        ) extends AbstractFileIteratingPrecondition
        {
            protected const NAME = 'NAME';
            protected const DESCRIPTION = 'DESCRIPTION';
            protected const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

            public bool $exitEarly = false;

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
                return $this->exitEarly;
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
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::exitEarly
     */
    public function testExitEarly(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        $this->filesystem
            ->fileExists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->fileFinder
            ->find(Argument::cetera())
            ->shouldNotBeCalled();
        $sut = $this->createSut();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $sut->exitEarly = true;

        $isFulfilled = $sut->isFulfilled($activeDirPath, $stagingDirPath);

        self::assertTrue($isFulfilled);

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
    }
}
