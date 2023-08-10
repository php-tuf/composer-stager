<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPathList;
use PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 */
final class AbstractPreconditionsTreeUnitTest extends PreconditionTestCase
{
    // @phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty
    public PathListInterface $exclusions;

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
        /** @var \PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface|\Prophecy\Prophecy\ObjectProphecy $child */
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
        $message = new TestTranslatableExceptionMessage(__METHOD__);

        $createLeaf = function (bool $isFulfilled) use ($message): PreconditionInterface {
            /** @var \Prophecy\Prophecy\ObjectProphecy|\PhpTuf\ComposerStager\Tests\TestUtils\TestSpyInterface $spy */
            $spy = $this->prophesize(TestSpyInterface::class);
            $spy->report('assertIsFulfilled')
                // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
                ->shouldBeCalledTimes(2);
            $spy = $spy->reveal();

            return new Class($isFulfilled, $message, $spy) extends AbstractPrecondition
            {
                public function __construct(
                    private readonly bool $isFulfilled,
                    private readonly TranslatableInterface $message,
                    private readonly TestSpyInterface $spy,
                ) {
                    parent::__construct(new TestTranslatableFactory());
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

        // @phpcs:disable SlevomatCodingStandard.Functions.RequireTrailingCommaInCall.MissingTrailingComma
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
        // @phpcs:enable

        self::assertFalse($sut->isFulfilled($this->activeDir, $this->stagingDir), 'Unfulfilled leaf status bubbled up properly.');
        self::assertSame($leaves, $sut->getLeaves());

        self::assertTranslatableException(function () use ($sut): void {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir);
        }, PreconditionException::class, $message);
    }
}
