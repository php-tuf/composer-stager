<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class AbstractPreconditionUnitTest extends PreconditionTestCase
{
    protected function createSut(): AbstractPrecondition
    {
        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class () extends AbstractPrecondition
        {
            public string $name = 'Name';
            public string $description = 'Description';
            public bool $isFulfilled = true;
            public string $fulfilledStatusMessage = 'Fulfilled';
            public string $unfulfilledStatusMessage = 'Unfulfilled';

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return $this->description;
            }

            protected function getFulfilledStatusMessage(): string
            {
                return $this->fulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->unfulfilledStatusMessage;
            }

            public function isFulfilled(PathInterface $activeDir, PathInterface $stagingDir): bool
            {
                return $this->isFulfilled;
            }
        };
    }

    /**
     * @covers ::getDescription
     * @covers ::getName
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
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();

        $sut = $this->createSut();
        $sut->name = $name;
        $sut->description = $description;
        $sut->isFulfilled = $isFulfilled;
        $sut->fulfilledStatusMessage = $fulfilledStatusMessage;
        $sut->unfulfilledStatusMessage = $unfulfilledStatusMessage;

        self::assertEquals($sut->getName(), $name);
        self::assertEquals($sut->getDescription(), $description);
        self::assertEquals($sut->isFulfilled($activeDir, $stagingDir), $isFulfilled);
        self::assertEquals($sut->getStatusMessage($activeDir, $stagingDir), $expectedStatusMessage);
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

    /** @covers ::assertIsFulfilled */
    public function testFulfilled(): void
    {
        $this->expectNotToPerformAssertions();

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();

        $sut = $this->createSut();
        $sut->isFulfilled = true;

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();
        $sut->isFulfilled = false;

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
