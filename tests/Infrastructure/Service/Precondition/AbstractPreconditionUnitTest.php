<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Domain\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait;
use PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 *
 * @property \PhpTuf\ComposerStager\Tests\TestSpyInterface|\Prophecy\Prophecy\ObjectProphecy $spy
 */
final class AbstractPreconditionUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->spy = $this->prophesize(TestSpyInterface::class);

        parent::setUp();
    }

    protected function createSut(): AbstractPrecondition
    {
        $spy = $this->spy->reveal();
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($spy, $translatableFactory) extends AbstractPrecondition
        {
            use TranslatableAwareTrait;

            public string $theName = 'Name';
            public string $theDescription = 'Description';
            public string $theFulfilledStatusMessage = 'Fulfilled';
            public string $theUnfulfilledStatusMessage = 'Unfulfilled';

            public function __construct(
                protected TestSpyInterface $spy,
                TranslatableFactoryInterface $translatableFactory,
            ) {
                parent::__construct(new TestTranslatableFactory(), new TestTranslator());

                $this->setTranslatableFactory($translatableFactory);
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

            public function assertIsFulfilled(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions = null,
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
    ): void {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->spy
            ->report([$this->activeDir, $this->stagingDir, $exclusions])
            ->shouldBeCalledTimes(2)
            ->willReturn($isFulfilled);

        $sut = $this->createSut();
        $sut->theName = $name;
        $sut->theDescription = $description;
        $sut->theFulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->theUnfulfilledStatusMessage = $unfulfilledStatusMessage;

        self::assertEquals($sut->getName(), $name);
        self::assertEquals($sut->getDescription(), $description);
        self::assertEquals($sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($this->activeDir, $this->stagingDir, $exclusions), $expectedStatusMessage);
        self::assertEquals($sut->getLeaves(), [$sut]);
    }

    public function providerBasicFunctionality(): array
    {
        return [
            [
                'name' => 'Name 1',
                'description' => 'Description 1',
                'exclusions' => null,
                'isFulfilled' => true,
                'fulfilledStatusMessage' => 'Fulfilled status message 1',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 1',
                'expectedStatusMessage' => 'Fulfilled status message 1',
            ],
            [
                'name' => 'Name 2',
                'description' => 'Description 2',
                'exclusions' => new TestPathList(),
                'isFulfilled' => false,
                'fulfilledStatusMessage' => 'Fulfilled status message 2',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 2',
                'expectedStatusMessage' => 'Unfulfilled status message 2',
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     */
    public function testFulfilled(): void
    {
        $this->spy
            ->report(Argument::cetera())
            ->willReturn(true);
        $this->spy
            ->report([$this->activeDir, $this->stagingDir])
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->spy
            ->report([$this->activeDir, $this->stagingDir, new TestPathList()])
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, new TestPathList());
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     */
    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $this->spy
            ->report([$this->activeDir, $this->stagingDir, new TestPathList()])
            ->willReturn(false);
        $sut = $this->createSut();
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $sut->theUnfulfilledStatusMessage = $message;


        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, new TestPathList());
        }, PreconditionException::class, $message);
    }
}
