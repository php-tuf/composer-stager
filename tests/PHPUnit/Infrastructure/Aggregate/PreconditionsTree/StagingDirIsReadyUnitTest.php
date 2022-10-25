<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagingDirIsReady;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagingDirIsReady
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsWritable
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagingDirIsReadyUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->stagingDirExists = $this->prophesize(StagingDirExistsInterface::class);
        $this->stagingDirIsWritable = $this->prophesize(StagingDirIsWritableInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirIsReady
    {
        $stagingDirExists = $this->stagingDirExists->reveal();
        $stagingDirIsWritable = $this->stagingDirIsWritable->reveal();

        return new StagingDirIsReady($stagingDirExists, $stagingDirIsWritable);
    }

    public function testFulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->stagingDirExists
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2);
        $this->stagingDirIsWritable
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2);

        parent::testFulfilled();
    }

    public function testUnfulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->stagingDirExists
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(2)
            ->willThrow(PreconditionException::class);

        parent::testUnfulfilled();
    }
}
