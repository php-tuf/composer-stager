<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\NoAbsoluteSymlinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoUnsupportedLinksExist;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoUnsupportedLinksExist
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\NoAbsoluteSymlinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noAbsoluteSymlinksExist
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\NoHardLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noHardLinksExist
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\NoLinksExistOnWindowsInterface|\Prophecy\Prophecy\ObjectProphecy $noLinksExistOnWindows
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointOutsideTheCodebaseInterface|\Prophecy\Prophecy\ObjectProphecy $noSymlinksPointOutsideTheCodebase
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\NoSymlinksPointToADirectoryInterface|\Prophecy\Prophecy\ObjectProphecy $noSymlinksPointToADirectory
 */
final class NoUnsupportedLinksExistUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->noAbsoluteSymlinksExist = $this->prophesize(NoAbsoluteSymlinksExistInterface::class);
        $this->noHardLinksExist = $this->prophesize(NoHardLinksExistInterface::class);
        $this->noLinksExistOnWindows = $this->prophesize(NoLinksExistOnWindowsInterface::class);
        $this->noSymlinksPointOutsideTheCodebase = $this->prophesize(NoSymlinksPointOutsideTheCodebaseInterface::class);
        $this->noSymlinksPointToADirectory = $this->prophesize(NoSymlinksPointToADirectoryInterface::class);
        $this->noAbsoluteSymlinksExist
            ->getLeaves()
            ->willReturn([$this->noAbsoluteSymlinksExist]);
        $this->noHardLinksExist
            ->getLeaves()
            ->willReturn([$this->noHardLinksExist]);
        $this->noLinksExistOnWindows
            ->getLeaves()
            ->willReturn([$this->noLinksExistOnWindows]);
        $this->noSymlinksPointOutsideTheCodebase
            ->getLeaves()
            ->willReturn([$this->noSymlinksPointOutsideTheCodebase]);
        $this->noSymlinksPointToADirectory
            ->getLeaves()
            ->willReturn([$this->noSymlinksPointToADirectory]);

        parent::setUp();
    }

    protected function createSut(): NoUnsupportedLinksExist
    {
        $noAbsoluteSymlinksExist = $this->noAbsoluteSymlinksExist->reveal();
        $noHardLinksExist = $this->noHardLinksExist->reveal();
        $noLinksExistOnWindows = $this->noLinksExistOnWindows->reveal();
        $noSymlinksPointOutsideTheCodebase = $this->noSymlinksPointOutsideTheCodebase->reveal();
        $noSymlinksPointToADirectory = $this->noSymlinksPointToADirectory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new NoUnsupportedLinksExist(
            $noAbsoluteSymlinksExist,
            $noHardLinksExist,
            $noLinksExistOnWindows,
            $noSymlinksPointOutsideTheCodebase,
            $noSymlinksPointToADirectory,
            $translatableFactory,
        );
    }

    public function testFulfilled(): void
    {
        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noHardLinksExist
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noLinksExistOnWindows
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noSymlinksPointOutsideTheCodebase
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noSymlinksPointToADirectory
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('There are no unsupported links in the codebase.');
    }

    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions));
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class);
    }
}
