<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksPointOutsideTheCodebaseInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\NoUnsupportedLinksExist;
use PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\NoUnsupportedLinksExist
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoAbsoluteLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noAbsoluteLinksExist
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoHardLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noHardLinksExist
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksExistOnWindowsInterface|\Prophecy\Prophecy\ObjectProphecy $noLinksExistOnWindows
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\NoLinksPointOutsideTheCodebaseInterface|\Prophecy\Prophecy\ObjectProphecy $noLinksPointOutsideTheCodebase
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class NoUnsupportedLinksExistUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->noAbsoluteLinksExist = $this->prophesize(NoAbsoluteLinksExistInterface::class);
        $this->noHardLinksExist = $this->prophesize(NoHardLinksExistInterface::class);
        $this->noLinksExistOnWindows = $this->prophesize(NoLinksExistOnWindowsInterface::class);
        $this->noLinksPointOutsideTheCodebase = $this->prophesize(NoLinksPointOutsideTheCodebaseInterface::class);

        parent::setUp();
    }

    protected function createSut(): NoUnsupportedLinksExist
    {
        $noAbsoluteLinksExist = $this->noAbsoluteLinksExist->reveal();
        $noHardLinksExist = $this->noHardLinksExist->reveal();
        $noLinksExistOnWindows = $this->noLinksExistOnWindows->reveal();
        $noLinksPointOutsideTheCodebase = $this->noLinksPointOutsideTheCodebase->reveal();

        return new NoUnsupportedLinksExist($noAbsoluteLinksExist, $noHardLinksExist, $noLinksExistOnWindows, $noLinksPointOutsideTheCodebase);
    }

    public function testFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->noAbsoluteLinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noHardLinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noLinksExistOnWindows
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noLinksPointOutsideTheCodebase
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('There are no unsupported links in the codebase.');
    }

    public function testUnfulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->noAbsoluteLinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(PreconditionException::class);

        $this->doTestUnfulfilled('There are unsupported links in the codebase.');
    }
}
