<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommonPreconditions;
use PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommonPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface|\Prophecy\Prophecy\ObjectProphecy $activeAndStagingDirsAreDifferent
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsWritable
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface|\Prophecy\Prophecy\ObjectProphecy $composerIsAvailable
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class CommonPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->composerIsAvailable = $this->prophesize(ComposerIsAvailableInterface::class);
        $this->activeDirExists = $this->prophesize(ActiveDirExistsInterface::class);
        $this->activeDirIsWritable = $this->prophesize(ActiveDirIsWritableInterface::class);
        $this->activeAndStagingDirsAreDifferent = $this->prophesize(ActiveAndStagingDirsAreDifferentInterface::class);

        parent::setUp();
    }

    protected function createSut(): CommonPreconditions
    {
        $composerIsAvailable = $this->composerIsAvailable->reveal();
        $activeDirExists = $this->activeDirExists->reveal();
        $activeDirIsWritable = $this->activeDirIsWritable->reveal();
        $activeAndStagingDirsAreDifferent = $this->activeAndStagingDirsAreDifferent->reveal();

        return new CommonPreconditions(
            $activeAndStagingDirsAreDifferent,
            $activeDirExists,
            $activeDirIsWritable,
            $composerIsAvailable,
        );
    }

    public function testFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->composerIsAvailable
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirExists
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsWritable
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
        $this->composerIsAvailable
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(PreconditionException::class);

        $this->doTestUnfulfilled('The common preconditions are unfulfilled.');
    }
}
