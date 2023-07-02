<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @property \PhpTuf\ComposerStager\API\Path\Value\PathListInterface $exclusions
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsReady
 */
final class CleanerPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->commonPreconditions
            ->getLeaves()
            ->willReturn([$this->commonPreconditions]);
        $this->stagingDirIsReady = $this->prophesize(StagingDirIsReadyInterface::class);
        $this->stagingDirIsReady
            ->getLeaves()
            ->willReturn([$this->stagingDirIsReady]);

        parent::setUp();
    }

    protected function createSut(): CleanerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new CleanerPreconditions($commonPreconditions, $stagingDirIsReady, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsReady
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The preconditions for removing the staging directory are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);

        $this->doTestUnfulfilled(new TestTranslatableExceptionMessage($message));
    }
}
