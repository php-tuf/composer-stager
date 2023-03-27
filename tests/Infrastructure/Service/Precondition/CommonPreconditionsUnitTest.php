<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CommonPreconditions;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CommonPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface|\Prophecy\Prophecy\ObjectProphecy $activeAndStagingDirsAreDifferent
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsReadyInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsReady
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface|\Prophecy\Prophecy\ObjectProphecy $composerIsAvailable
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class CommonPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->activeAndStagingDirsAreDifferent = $this->prophesize(ActiveAndStagingDirsAreDifferentInterface::class);
        $this->activeAndStagingDirsAreDifferent
            ->getLeaves()
            ->willReturn([$this->activeAndStagingDirsAreDifferent]);
        $this->activeDirIsReady = $this->prophesize(ActiveDirIsReadyInterface::class);
        $this->activeDirIsReady
            ->getLeaves()
            ->willReturn([$this->activeDirIsReady]);
        $this->composerIsAvailable = $this->prophesize(ComposerIsAvailableInterface::class);
        $this->composerIsAvailable
            ->getLeaves()
            ->willReturn([$this->composerIsAvailable]);

        parent::setUp();
    }

    protected function createSut(): CommonPreconditions
    {
        $activeAndStagingDirsAreDifferent = $this->activeAndStagingDirsAreDifferent->reveal();
        $activeDirIsReady = $this->activeDirIsReady->reveal();
        $composerIsAvailable = $this->composerIsAvailable->reveal();

        return new CommonPreconditions($activeAndStagingDirsAreDifferent, $activeDirIsReady, $composerIsAvailable);
    }

    public function testFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->composerIsAvailable
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsReady
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The common preconditions are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(PreconditionException::class);

        $this->doTestUnfulfilled('The common preconditions are unfulfilled.');
    }
}
