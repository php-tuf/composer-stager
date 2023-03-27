<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteSymlinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointToADirectoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoUnsupportedLinksExist;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoUnsupportedLinksExist
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteSymlinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noAbsoluteSymlinksExist
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noHardLinksExist
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface|\Prophecy\Prophecy\ObjectProphecy $noLinksExistOnWindows
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointOutsideTheCodebaseInterface|\Prophecy\Prophecy\ObjectProphecy $noSymlinksPointOutsideTheCodebase
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoSymlinksPointToADirectoryInterface|\Prophecy\Prophecy\ObjectProphecy $noSymlinksPointToADirectory
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
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

        return new NoUnsupportedLinksExist(
            $noAbsoluteSymlinksExist,
            $noHardLinksExist,
            $noLinksExistOnWindows,
            $noSymlinksPointOutsideTheCodebase,
            $noSymlinksPointToADirectory,
        );
    }

    public function testFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noHardLinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noLinksExistOnWindows
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noSymlinksPointOutsideTheCodebase
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noSymlinksPointToADirectory
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('There are no unsupported links in the codebase.');
    }

    public function testUnfulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->noAbsoluteSymlinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(PreconditionException::class);

        $this->doTestUnfulfilled('There are unsupported links in the codebase.');
    }
}
