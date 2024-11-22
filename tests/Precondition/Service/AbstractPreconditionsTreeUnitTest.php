<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;
use PhpTuf\ComposerStager\API\Translation\Value\TranslatableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestFulfilledPrecondition;
use PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\TestUnfulfilledPrecondition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractPreconditionsTree::class)]
final class AbstractPreconditionsTreeUnitTest extends PreconditionUnitTestCase
{
    protected function createSut(...$children): AbstractPreconditionsTree
    {
        $environment = $this->environment->reveal();
        $translatableFactory = self::createTranslatableFactory();

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($environment, $translatableFactory, ...$children) extends AbstractPreconditionsTree
        {
            public const NAME = 'NAME';
            public const DESCRIPTION = 'DESCRIPTION';
            public const FULFILLED_STATUS_MESSAGE = 'FULFILLED_STATUS_MESSAGE';

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

    public function testGetters(): void
    {
        // Neutralize the "getters" test for this special case class.
        $this->expectNotToPerformAssertions();
    }

    private function createPrecondition(?string $class = null): PreconditionInterface
    {
        $environment = $this->environment->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new $class($environment, $translatableFactory);
    }

    #[DataProvider('providerBasicFunctionality')]
    public function testBasicFunctionality(string $class, ?PathListInterface $exclusions, int $timeout): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        // Pass a mock child into the SUT so the behavior of ::assertIsFulfilled
        // can be controlled indirectly, without overriding the method on the SUT
        // itself and preventing it from actually being exercised.
        $child = $this->prophesize(PreconditionInterface::class);
        $child->getLeaves()
            ->willReturn([$child]);

        $child->assertIsFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout)
            ->shouldBeCalledOnce();

        if (!$class::IS_FULFILLED) {
            $child->assertIsFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout)
                ->willThrow(PreconditionException::class);
        }

        $child = $child->reveal();

        /** @var \PhpTuf\ComposerStager\Tests\TestDoubles\Precondition\Service\AbstractTestPrecondition $sut */
        $sut = $this->createSut($child);

        self::assertSame($sut::NAME, $sut->getName()->trans());
        self::assertSame($sut::DESCRIPTION, $sut->getDescription()->trans());
        self::assertSame($class::IS_FULFILLED, $sut->isFulfilled($activeDirPath, $stagingDirPath, $exclusions, $timeout));
        self::assertSame([$child], $sut->getLeaves());
    }

    public static function providerBasicFunctionality(): array
    {
        return [
            'Fulfilled, without exclusions' => [
                'class' => TestFulfilledPrecondition::class,
                'exclusions' => null,
                'timeout' => 10,
            ],
            'Unfulfilled, with exclusions' => [
                'class' => TestUnfulfilledPrecondition::class,
                'exclusions' => self::createPathList(),
                'timeout' => 100,
            ],
        ];
    }

    public function testIsFulfilledBubbling(): void
    {
        $leaves = [
            $this->createPrecondition(TestFulfilledPrecondition::class),
            $this->createPrecondition(TestFulfilledPrecondition::class),
            $this->createPrecondition(TestFulfilledPrecondition::class),
            $this->createPrecondition(TestUnfulfilledPrecondition::class),
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

        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();

        self::assertFalse($sut->isFulfilled($activeDirPath, $stagingDirPath), 'Unfulfilled leaf status bubbled up properly.');
        self::assertSame($leaves, $sut->getLeaves());

        self::assertTranslatableException(static function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath);
        }, PreconditionException::class, TestUnfulfilledPrecondition::UNFULFILLED_STATUS_MESSAGE);
    }
}
