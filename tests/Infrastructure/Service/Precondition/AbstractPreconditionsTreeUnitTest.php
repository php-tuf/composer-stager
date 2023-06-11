<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Translation\Service\TranslatorInterface;
use PhpTuf\ComposerStager\Domain\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPathList;
use PhpTuf\ComposerStager\Tests\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathListInterface $exclusions
 */
final class AbstractPreconditionsTreeUnitTest extends PreconditionTestCase
{
    protected function createSut(...$children): AbstractPreconditionsTree
    {
        $translatableFactory = new TestTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($translatableFactory, ...$children) extends AbstractPreconditionsTree
        {
            public string $name = 'Name';
            public string $description = 'Description';
            public bool $isFulfilled = true;
            public string $fulfilledStatusMessage = 'Fulfilled';

            public function getName(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->name);
            }

            public function getDescription(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->description);
            }

            protected function getFulfilledStatusMessage(): TranslatableInterface
            {
                return new TestTranslatableMessage($this->fulfilledStatusMessage);
            }
        };
    }

    /**
     * @covers ::getDescription
     * @covers ::getLeaves
     * @covers ::getName
     * @covers ::getStatusMessage
     *
     * @dataProvider providerBasicFunctionality
     *
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function testBasicFunctionality(
        string $name,
        string $description,
        bool $isFulfilled,
        string $fulfilledStatusMessage,
        string $unfulfilledStatusMessage,
        string $expectedStatusMessage,
        ?TestPathList $exclusions,
    ): void {
        // Pass a mock child into the SUT so the behavior of ::assertIsFulfilled
        // can be controlled indirectly, without overriding the method on the SUT
        // itself and preventing it from actually being exercised.
        /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface|\Prophecy\Prophecy\ObjectProphecy $child */
        $child = $this->prophesize(PreconditionInterface::class);
        $child->getLeaves()
            ->willReturn([$child]);

        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $child->assertIsFulfilled($this->activeDir, $this->stagingDir, $exclusions)
            ->shouldBeCalledOnce();

        if (!$isFulfilled) {
            $child->assertIsFulfilled($this->activeDir, $this->stagingDir, $exclusions)
                ->willThrow(PreconditionException::class);
        }

        $child = $child->reveal();

        $sut = $this->createSut($child);

        $sut->name = $name;
        $sut->description = $description;
        $sut->isFulfilled = $isFulfilled;
        $sut->fulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->unfulfilledStatusMessage = $unfulfilledStatusMessage;

        self::assertEquals($name, $sut->getName());
        self::assertEquals($description, $sut->getDescription());
        self::assertEquals($isFulfilled, $sut->isFulfilled($this->activeDir, $this->stagingDir, $exclusions));
    }

    public function providerBasicFunctionality(): array
    {
        return [
            [
                'name' => 'Name 1',
                'description' => 'Description 1',
                'isFulfilled' => true,
                'fulfilledStatusMessage' => 'Fulfilled status message 1',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 1',
                'expectedStatusMessage' => 'Fulfilled status message 1',
                'exclusions' => null,
            ],
            [
                'name' => 'Name 2',
                'description' => 'Description 2',
                'isFulfilled' => false,
                'fulfilledStatusMessage' => 'Fulfilled status message 2',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 2',
                'expectedStatusMessage' => 'Unfulfilled status message 2',
                'exclusions' => new TestPathList(),
            ],
        ];
    }

    /** @covers ::getLeaves */
    public function testIsFulfilledBubbling(): void
    {
        $message = new TestTranslatableMessage(__METHOD__);

        $createLeaf = function (bool $isFulfilled) use ($message): PreconditionInterface {
            /** @var \Prophecy\Prophecy\ObjectProphecy|\PhpTuf\ComposerStager\Tests\TestSpyInterface $spy */
            $spy = $this->prophesize(TestSpyInterface::class);
            $spy->report('assertIsFulfilled')
                // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
                ->shouldBeCalledTimes(2);
            $spy = $spy->reveal();

            return new Class($isFulfilled, $message, $spy, new TestTranslator()) extends AbstractPrecondition
            {
                public function __construct(
                    private readonly bool $isFulfilled,
                    private readonly TranslatableInterface $message,
                    private readonly TestSpyInterface $spy,
                    protected TranslatorInterface $translator,
                ) {
                    parent::__construct(new TestTranslatableFactory(), $translator);
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

                public function assertIsFulfilled(
                    PathInterface $activeDir,
                    PathInterface $stagingDir,
                    ?PathListInterface $exclusions = null,
                ): void {
                    $this->spy->report('assertIsFulfilled');

                    if (!$this->isFulfilled) {
                        throw new PreconditionException($this, $this->message);
                    }
                }
            };
        };

        $leaves = [
            $createLeaf(true),
            $createLeaf(true),
            $createLeaf(true),
            $createLeaf(false),
        ];

        // phpcs:disable SlevomatCodingStandard.Functions.RequireTrailingCommaInCall.MissingTrailingComma
        //   Trailing commas on this array make it cross PhpStorm's complexity threshold:
        //   "Code fragment is too complex to parse and will be treated as plain text."
        $sut = $this->createSut(
            $leaves[0],
            $this->createSut(
                $this->createSut(
                    $leaves[1],
                )
            ),
            $this->createSut(
                $this->createSut(
                    $this->createSut(
                        $this->createSut(
                            $leaves[2],
                        )
                    )
                )
            ),
            $this->createSut(
                $this->createSut(
                    $this->createSut(
                        $this->createSut(
                            $this->createSut(
                                $this->createSut(
                                    $this->createSut(
                                        $this->createSut(
                                            $this->createSut(
                                                $this->createSut(
                                                    $leaves[3],
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
        // phpcs:enable

        self::assertFalse($sut->isFulfilled($this->activeDir, $this->stagingDir), 'Unfulfilled leaf status bubbled up properly.');
        self::assertSame($leaves, $sut->getLeaves());

        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }
}
