<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\HostSupportsRunningProcessesInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommonPreconditions;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\CommonPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 */
final class CommonPreconditionsUnitTest extends PreconditionTestCase
{
    private ActiveAndStagingDirsAreDifferentInterface|ObjectProphecy $activeAndStagingDirsAreDifferent;
    private ActiveDirIsReadyInterface|ObjectProphecy $activeDirIsReady;
    private ComposerIsAvailableInterface|ObjectProphecy $composerIsAvailable;
    private HostSupportsRunningProcessesInterface|ObjectProphecy $hostSupportsRunningProcesses;

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
        $this->hostSupportsRunningProcesses = $this->prophesize(HostSupportsRunningProcessesInterface::class);
        $this->hostSupportsRunningProcesses
            ->getLeaves()
            ->willReturn([$this->hostSupportsRunningProcesses]);

        parent::setUp();
    }

    protected function createSut(): CommonPreconditions
    {
        $activeAndStagingDirsAreDifferent = $this->activeAndStagingDirsAreDifferent->reveal();
        $activeDirIsReady = $this->activeDirIsReady->reveal();
        $composerIsAvailable = $this->composerIsAvailable->reveal();
        $hostSupportsRunningProcesses = $this->hostSupportsRunningProcesses->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new CommonPreconditions(
            $translatableFactory,
            $activeAndStagingDirsAreDifferent,
            $activeDirIsReady,
            $composerIsAvailable,
            $hostSupportsRunningProcesses,
        );
    }

    public function testFulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->composerIsAvailable
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsReady
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->hostSupportsRunningProcesses
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The common preconditions are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->willThrow($previous);

        $this->doTestUnfulfilled($message);
    }
}
