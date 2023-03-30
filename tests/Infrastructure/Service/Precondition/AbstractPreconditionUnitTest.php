<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Domain\Value\PathList\PathListInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\PathList\TestPathList;
use PhpTuf\ComposerStager\Tests\TestSpyInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
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

        // Create a concrete implementation for testing since the SUT, being
        // abstract, can't be instantiated directly.
        return new class ($spy) extends AbstractPrecondition
        {
            public string $theName = 'Name';
            public string $theDescription = 'Description';
            public string $theFulfilledStatusMessage = 'Fulfilled';
            public string $theUnfulfilledStatusMessage = 'Unfulfilled';

            public function __construct(protected TestSpyInterface $spy)
            {
            }

            public function getName(): string
            {
                return $this->theName;
            }

            public function getDescription(): string
            {
                return $this->theDescription;
            }

            protected function getFulfilledStatusMessage(): string
            {
                return $this->theFulfilledStatusMessage;
            }

            protected function getUnfulfilledStatusMessage(): string
            {
                return $this->theUnfulfilledStatusMessage;
            }

            public function isFulfilled(
                PathInterface $activeDir,
                PathInterface $stagingDir,
                ?PathListInterface $exclusions = null,
            ): bool {
                return $this->spy->report(func_get_args());
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
     */
    public function testBasicFunctionality(
        $name,
        $description,
        $exclusions,
        $isFulfilled,
        $fulfilledStatusMessage,
        $unfulfilledStatusMessage,
        $expectedStatusMessage,
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

    /** @covers ::assertIsFulfilled */
    public function testFulfilled(): void
    {
        $this->spy
            ->report(Argument::cetera())
            ->willReturn(true);
        $this->spy
            ->report([$this->activeDir, $this->stagingDir, null])
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

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $this->spy
            ->report([$this->activeDir, $this->stagingDir, new TestPathList()])
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, new TestPathList());
    }
}
