<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Aggregate\PreconditionsTree;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\BeginnerPreconditions;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition\PreconditionTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\BeginnerPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 *
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\NoUnsupportedLinksExistInterface|\Prophecy\Prophecy\ObjectProphecy $noUnsupportedLinksExist
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExistInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirDoesNotExist
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class BeginnerPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->noUnsupportedLinksExist = $this->prophesize(NoUnsupportedLinksExistInterface::class);
        $this->stagingDirDoesNotExist = $this->prophesize(StagingDirDoesNotExistInterface::class);

        parent::setUp();
    }

    protected function createSut(): BeginnerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $noUnsupportedLinksExist = $this->noUnsupportedLinksExist->reveal();
        $stagingDirDoesNotExist = $this->stagingDirDoesNotExist->reveal();

        return new BeginnerPreconditions($commonPreconditions, $noUnsupportedLinksExist, $stagingDirDoesNotExist);
    }

    public function testFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->commonPreconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noUnsupportedLinksExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirDoesNotExist
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The preconditions for beginning the staging process are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $exclusions = $this->exclusions;
        $this->commonPreconditions
            ->assertIsFulfilled($activeDir, $stagingDir, $exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE)
            ->willThrow(PreconditionException::class);

        $this->doTestUnfulfilled('The preconditions for beginning the staging process are unfulfilled.');
    }
}
