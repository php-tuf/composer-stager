<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagingDirIsReady;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagingDirIsReady
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsWritable
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagingDirIsReadyUnitTest extends TestCase
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
        $this->stagingDirExists = $this->prophesize(StagingDirExistsInterface::class);
        $this->stagingDirIsWritable = $this->prophesize(StagingDirIsWritableInterface::class);
    }

    protected function createSut(): StagingDirIsReady
    {
        $stagingDirExists = $this->stagingDirExists->reveal();
        $stagingDirIsWritable = $this->stagingDirIsWritable->reveal();

        return new StagingDirIsReady($stagingDirExists, $stagingDirIsWritable);
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
        $this->stagingDirExists
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce();
        $this->stagingDirIsWritable
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
        $this->stagingDirExists
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $this->stagingDirIsWritable
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled($activeDir, $stagingDir);

        self::assertFalse($isFulfilled);

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
