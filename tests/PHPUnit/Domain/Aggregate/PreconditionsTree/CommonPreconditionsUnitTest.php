<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditions;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditions
 *
 * @uses \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface|\Prophecy\Prophecy\ObjectProphecy $activeAndStagingDirsAreDifferentPrecondition
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirExistsPrecondition
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsWritablePrecondition
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface|\Prophecy\Prophecy\ObjectProphecy $composerIsAvailable
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class CommonPreconditionsUnitTest extends TestCase
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
        $this->composerIsAvailable = $this->prophesize(ComposerIsAvailableInterface::class);
        $this->activeDirExistsPrecondition = $this->prophesize(ActiveDirExistsInterface::class);
        $this->activeDirIsWritablePrecondition = $this->prophesize(ActiveDirIsWritableInterface::class);
        $this->activeAndStagingDirsAreDifferentPrecondition = $this->prophesize(ActiveAndStagingDirsAreDifferentInterface::class);
    }

    protected function createSut(): CommonPreconditions
    {
        $composerIsAvailable = $this->composerIsAvailable->reveal();
        $activeDirExistsPrecondition = $this->activeDirExistsPrecondition->reveal();
        $activeDirIsWritablePrecondition = $this->activeDirIsWritablePrecondition->reveal();
        $activeAndStagingDirsAreDifferentPrecondition = $this->activeAndStagingDirsAreDifferentPrecondition->reveal();
        return new CommonPreconditions(
            $composerIsAvailable,
            $activeDirExistsPrecondition,
            $activeDirIsWritablePrecondition,
            $activeAndStagingDirsAreDifferentPrecondition
        );
    }

    /**
     * @covers ::__construct
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerIsAvailable
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->activeDirExistsPrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->activeDirIsWritablePrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $this->activeAndStagingDirsAreDifferentPrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $sut = $this->createSut();

        self::assertTrue($sut->isFulfilled($activeDir, $stagingDir));
    }

    /**
     * @covers ::__construct
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerIsAvailable
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);
        $this->activeDirExistsPrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);
        $this->activeDirIsWritablePrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);
        $this->activeAndStagingDirsAreDifferentPrecondition
            ->isFulfilled($activeDir, $stagingDir)
            ->willReturn(false);

        $sut = $this->createSut();

        self::assertFalse($sut->isFulfilled($activeDir, $stagingDir));

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
