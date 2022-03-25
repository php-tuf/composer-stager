<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 *
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $path
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
class AbstractPreconditionUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
        $this->path = $this->prophesize(PathInterface::class);
    }

    protected function createSut(...$subPreconditions): AbstractPrecondition
    {
        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class (...$subPreconditions) extends AbstractPrecondition
        {
            public $isFulfilled = true;
            public $fulfilledStatusMessage = '';
            public $unfulfilledStatusMessage = '';

            public function getName(): string
            {
                return 'Name';
            }

            public function getDescription(): string
            {
                return 'Description';
            }

            protected function getFulfilledStatusMessage(): string
            {
                return $this->fulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->unfulfilledStatusMessage;
            }
        };
    }

    /**
     * @covers ::getStatusMessage
     *
     * @dataProvider providerBasicFunctionality
     */
    public function testBasicFunctionality(
        $name,
        $description,
        $isFulfilled,
        $fulfilledStatusMessage,
        $unfulfilledStatusMessage,
        $expectedStatusMessage
    ): void {
        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        $sut = new class () extends AbstractPrecondition
        {
            public $name = '';
            public $description = '';
            public $isFulfilled = true;
            public $fulfilledStatusMessage = '';
            public $unfulfilledStatusMessage = '';

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return $this->description;
            }

            public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
            {
                return $this->isFulfilled;
            }

            protected function getFulfilledStatusMessage(): string
            {
                return $this->fulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->unfulfilledStatusMessage;
            }
        };

        $sut->name = $name;
        $sut->description = $description;
        $sut->isFulfilled = $isFulfilled;
        $sut->fulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->unfulfilledStatusMessage = $unfulfilledStatusMessage;
        $path = $this->path->reveal();

        self::assertEquals($sut->getName(), $name);
        self::assertEquals($sut->getDescription(), $description);
        self::assertEquals($sut->isFulfilled($path, $path), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($path, $path), $expectedStatusMessage);
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
            ],
            [
                'name' => 'Name 2',
                'description' => 'Description 2',
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
     * @covers ::isFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     */
    public function testWithNesting(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();

        $createMockSut = function (bool $return) use ($activeDir, $stagingDir): PreconditionInterface {
            /** @var \PhpTuf\ComposerStager\Domain\Service\Precondition\PreconditionInterface|\Prophecy\Prophecy\ObjectProphecy $prophecy */
            $prophecy = $this->prophesize(PreconditionInterface::class);
            $prophecy->isFulfilled($activeDir, $stagingDir)
                ->shouldBeCalledOnce()
                ->willReturn($return);
            return $prophecy->reveal();
        };

        $sut = $this->createSut(
            $createMockSut(true),
            $this->createSut(
                $this->createSut(
                    $createMockSut(true)
                )
            ),
            $this->createSut(
                $this->createSut(
                    $this->createSut(
                        $this->createSut(
                            $createMockSut(true)
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
                                                    $createMockSut(false)
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

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
