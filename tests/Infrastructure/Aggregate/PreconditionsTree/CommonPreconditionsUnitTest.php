<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommonPreconditions;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommonPreconditions
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferentInterface|\Prophecy\Prophecy\ObjectProphecy $activeAndStagingDirsAreDifferent
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsWritable
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\CodebaseContainsNoSymlinksInterface|\Prophecy\Prophecy\ObjectProphecy $codebaseContainsNoSymlinksInterface
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
        $this->activeDirExists = $this->prophesize(ActiveDirExistsInterface::class);
        $this->activeDirIsWritable = $this->prophesize(ActiveDirIsWritableInterface::class);
        $this->activeAndStagingDirsAreDifferent = $this->prophesize(ActiveAndStagingDirsAreDifferentInterface::class);
        $this->codebaseContainsNoSymlinksInterface = $this->prophesize(CodebaseContainsNoSymlinksInterface::class);
    }

    protected function createSut(): CommonPreconditions
    {
        $composerIsAvailable = $this->composerIsAvailable->reveal();
        $activeDirExists = $this->activeDirExists->reveal();
        $activeDirIsWritable = $this->activeDirIsWritable->reveal();
        $activeAndStagingDirsAreDifferent = $this->activeAndStagingDirsAreDifferent->reveal();
        $codebaseContainsNoSymlinksInterface = $this->codebaseContainsNoSymlinksInterface->reveal();

        return new CommonPreconditions(
            $composerIsAvailable,
            $activeDirExists,
            $activeDirIsWritable,
            $activeAndStagingDirsAreDifferent,
            $codebaseContainsNoSymlinksInterface,
        );
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerIsAvailable
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce();
        $this->activeDirExists
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce();
        $this->activeDirIsWritable
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce();
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertTrue($isFulfilled);
    }

    /**
     * @covers ::__construct
     * @covers ::assertIsFulfilled
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->composerIsAvailable
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $this->activeDirExists
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $this->activeDirIsWritable
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $this->activeAndStagingDirsAreDifferent
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
