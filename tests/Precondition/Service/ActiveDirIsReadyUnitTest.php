<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirExistsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsWritableInterface;
use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveDirIsReady;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveDirIsReady
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirExistsInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirExists
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\ActiveDirIsWritableInterface|\Prophecy\Prophecy\ObjectProphecy $activeDirIsWritable
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
        $translatableFactory = new TestTranslatableFactory();

        return new ActiveDirIsReady($stagingDirExists, $stagingDirIsWritable, $translatableFactory);
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
        $previous = self::createTestPreconditionException(__METHOD__);
        $this->activeDirExists
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);

        $this->doTestUnfulfilled($previous->getMessage());
    }
}
