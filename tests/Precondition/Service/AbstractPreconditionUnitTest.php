<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition;
use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition */
final class AbstractPreconditionUnitTest extends PreconditionTestCase
{
    private TestSpyInterface|ObjectProphecy $spy;
    
    protected function setUp(): void
    {
        $this->spy = $this->prophesize(TestSpyInterface::class);

        parent::setUp();
    }

    protected function createSut(): AbstractPrecondition
    {
        $environment = $this->environment->reveal();
        $spy = $this->spy->reveal();
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($environment, $spy, $translatableFactory) extends AbstractPrecondition
        {
            use TranslatableAwareTrait;

            public string $theName = 'Name';
            public string $theDescription = 'Description';
            public string $theFulfilledStatusMessage = 'Fulfilled';
            public string $theUnfulfilledStatusMessage = 'Unfulfilled';

            public function __construct(
                EnvironmentInterface $environment,
                protected TestSpyInterface $spy,
                TranslatableFactoryInterface $translatableFactory,
            ) {
                parent::__construct($environment, $translatableFactory);
            }

            public function getName(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->theName);
            }

            public function getDescription(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->theDescription);
            }

            protected function getFulfilledStatusMessage(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->theFulfilledStatusMessage);
            }

            protected function doAssertIsFulfilled(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions = null,
                int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
            ): void {
                if (!$this->spy->report(func_get_args())) {
                    throw TestCase::createTestPreconditionException($this->theUnfulfilledStatusMessage);
                }
            }
        };
    }

    /**
     * @covers ::__construct
     * @covers ::getDescription
     * @covers ::getLeaves
     * @covers ::getName
     * @covers ::getStatusMessage
     * @covers ::isFulfilled
     *
     * @dataProvider providerBasicFunctionality
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testBasicFunctionality(
        string $name,
        string $description,
        ?PathListInterface $exclusions,
        bool $isFulfilled,
        string $fulfilledStatusMessage,
        string $unfulfilledStatusMessage,
        string $expectedStatusMessage,
        int $timeout,
    ): void {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->spy
            ->report([$activeDirPath, $stagingDirPath, $exclusions, $timeout])
            ->shouldBeCalledTimes(2)
            ->willReturn($isFulfilled);

        $sut = $this->createSut();
        $sut->theName = $name;
        $sut->theDescription = $description;
        $sut->theFulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->theUnfulfilledStatusMessage = $unfulfilledStatusMessage;

        self::assertEquals($sut->getName(), $name);
        self::assertEquals($sut->getDescription(), $description);
        self::assertEquals($sut->isFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($activeDirPath, $stagingDirPath, $exclusions, $timeout), $expectedStatusMessage);
        self::assertEquals($sut->getLeaves(), [$sut]);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            'Fulfilled, without exclusions' => [
                'name' => 'Name 1',
                'description' => 'Description 1',
                'exclusions' => null,
                'isFulfilled' => true,
                'fulfilledStatusMessage' => 'Fulfilled status message 1',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 1',
                'expectedStatusMessage' => 'Fulfilled status message 1',
                'timeout' => 0,
            ],
            'Unfulfilled, with exclusions' => [
                'name' => 'Name 2',
                'description' => 'Description 2',
                'exclusions' => new TestPathList(),
                'isFulfilled' => false,
                'fulfilledStatusMessage' => 'Fulfilled status message 2',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 2',
                'expectedStatusMessage' => 'Unfulfilled status message 2',
                'timeout' => 100,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     */
    public function testFulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();
        $timeout = 42;

        $this->spy
            ->report(Argument::cetera())
            ->willReturn(true);
        $this->spy
            ->report([$activeDirPath, $stagingDirPath, $this->exclusions, $timeout])
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->spy
            ->report([$activeDirPath, $stagingDirPath, new TestPathList(), $timeout])
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, null, $timeout);
        $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, new TestPathList(), $timeout);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     */
    public function testUnfulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();
        $exclusions = $this->exclusions;
        $timeout = 42;

        $message = __METHOD__;
        $this->spy
            ->report([$activeDirPath, $stagingDirPath, $exclusions, $timeout])
            ->willReturn(false);
        $sut = $this->createSut();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $sut->theUnfulfilledStatusMessage = $message;

        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath, $exclusions, $timeout): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout);
        }, PreconditionException::class, $message);
    }
}
