<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $path
 */
class AbstractPreconditionUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->path = $this->prophesize(PathInterface::class);
    }

    protected function createSut(): AbstractPrecondition
    {
        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class () extends AbstractPrecondition
        {
            public $isFulfilled = true;
            public $fulfilledStatusMessage = '';
            public $unfulfilledStatusMessage = '';

            protected function getFulfilledStatusMessage(): string
            {
                return $this->fulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->unfulfilledStatusMessage;
            }

            public static function getName(): string
            {
                return 'Name';
            }

            public static function getDescription(): string
            {
                return 'Description';
            }

            public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
            {
                return $this->isFulfilled;
            }
        };
    }

    /**
     * @covers ::getStatusMessage
     *
     * @dataProvider providerTest
     */
    public function test($isFulfilled, $filledStatusMessage, $unfulfilledStatusMessage, $expectedStatusMessage): void
    {
        $sut = $this->createSut();
        $sut->isFulfilled = $isFulfilled;
        $sut->fulfilledStatusMessage = $filledStatusMessage;
        $sut->unfulfilledStatusMessage = $unfulfilledStatusMessage;
        $path = $this->path->reveal();

        self::assertEquals($sut->isFulfilled($path, $path), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($path, $path), $expectedStatusMessage);
    }

    public function providerTest(): array
    {
        return [
            [
                'isFulfilled' => true,
                'fulfilledStatusMessage' => 'Fulfilled status message 1',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 1',
                'expectedStatusMessage' => 'Fulfilled status message 1',
            ],
            [
                'isFulfilled' => false,
                'fulfilledStatusMessage' => 'Fulfilled status message 2',
                'unfulfilledStatusMessage' => 'Unfulfilled status message 2',
                'expectedStatusMessage' => 'Unfulfilled status message 2',
            ],
        ];
    }

    /**
     * @covers ::assertIsFulfilled
     */
    public function testAssertFulfilled(): void
    {
        $this->expectNotToPerformAssertions();

        $sut = $this->createSut();
        $sut->isFulfilled = true;
        $path = $this->path->reveal();

        $sut->assertIsFulfilled($path, $path);
    }

    /**
     * @covers ::assertIsFulfilled
     *
     * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
     */
    public function testAssertUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $sut = $this->createSut();
        $sut->isFulfilled = false;
        $path = $this->path->reveal();

        $sut->assertIsFulfilled($path, $path);
    }
}
