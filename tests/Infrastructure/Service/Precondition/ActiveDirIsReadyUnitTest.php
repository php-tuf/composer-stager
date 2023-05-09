<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirIsReady;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirIsReady
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirExists
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsWritable
 */
final class ActiveDirIsReadyUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->activeDirExists = $this->prophesize(ActiveDirExistsInterface::class);
        $this->activeDirExists
            ->getLeaves()
            ->willReturn([$this->activeDirExists]);
        $this->activeDirIsWritable = $this->prophesize(ActiveDirIsWritableInterface::class);
        $this->activeDirIsWritable
            ->getLeaves()
            ->willReturn([$this->activeDirIsWritable]);

        parent::setUp();
    }

    protected function createSut(): ActiveDirIsReady
    {
        $stagingDirExists = $this->activeDirExists->reveal();
        $stagingDirIsWritable = $this->activeDirIsWritable->reveal();

        return new ActiveDirIsReady($stagingDirExists, $stagingDirIsWritable);
    }

    public function testFulfilled(): void
    {
        $this->activeDirExists
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->activeDirIsWritable
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The active directory is ready to use.');
    }

    public function testUnfulfilled(): void
    {
        $unfulfilledChild = new TestPrecondition();
        $previous = new PreconditionException($unfulfilledChild);
        $this->activeDirExists
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow($previous);

        $this->doTestUnfulfilled($previous->getMessage());
    }
}
