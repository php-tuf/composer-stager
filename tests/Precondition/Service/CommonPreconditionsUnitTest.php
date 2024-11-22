<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsReadyInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\HostSupportsRunningProcessesInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoNestingOnWindowsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\RsyncIsAvailableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommonPreconditions;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(CommonPreconditions::class)]
final class CommonPreconditionsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Common preconditions';
    protected const DESCRIPTION = 'The preconditions common to all operations.';
    protected const FULFILLED_STATUS_MESSAGE = 'The common preconditions are fulfilled.';

    private ActiveAndStagingDirsAreDifferentInterface|ObjectProphecy $activeAndStagingDirsAreDifferent;
    private ActiveDirIsReadyInterface|ObjectProphecy $activeDirIsReady;
    private ComposerIsAvailableInterface|ObjectProphecy $composerIsAvailable;
    private HostSupportsRunningProcessesInterface|ObjectProphecy $hostSupportsRunningProcesses;
    private ObjectProphecy|NoNestingOnWindowsInterface $noNestingOnWindows;
    private RsyncIsAvailableInterface|ObjectProphecy $rsyncIsAvailable;

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
        $this->noNestingOnWindows = $this->prophesize(NoNestingOnWindowsInterface::class);
        $this->noNestingOnWindows
            ->getLeaves()
            ->willReturn([$this->noNestingOnWindows]);
        $this->rsyncIsAvailable = $this->prophesize(RsyncIsAvailableInterface::class);
        $this->rsyncIsAvailable
            ->getLeaves()
            ->willReturn([$this->rsyncIsAvailable]);

        parent::setUp();
    }

    protected function createSut(): CommonPreconditions
    {
        $activeAndStagingDirsAreDifferent = $this->activeAndStagingDirsAreDifferent->reveal();
        $activeDirIsReady = $this->activeDirIsReady->reveal();
        $composerIsAvailable = $this->composerIsAvailable->reveal();
        $environment = $this->environment->reveal();
        $hostSupportsRunningProcesses = $this->hostSupportsRunningProcesses->reveal();
        $noNestingOnWindows = $this->noNestingOnWindows->reveal();
        $rsyncIsAvailable = $this->rsyncIsAvailable->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new CommonPreconditions(
            $environment,
            $translatableFactory,
            $activeAndStagingDirsAreDifferent,
            $activeDirIsReady,
            $composerIsAvailable,
            $hostSupportsRunningProcesses,
            $noNestingOnWindows,
            $rsyncIsAvailable,
        );
    }

    public function testFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $this->composerIsAvailable
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsReady
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->hostSupportsRunningProcesses
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noNestingOnWindows
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->rsyncIsAvailable
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(self::FULFILLED_STATUS_MESSAGE, $activeDirPath, $stagingDirPath, $timeout);
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->willThrow($previous);

        $this->doTestUnfulfilled($message, null, $activeDirPath, $stagingDirPath, $timeout);
    }
}
