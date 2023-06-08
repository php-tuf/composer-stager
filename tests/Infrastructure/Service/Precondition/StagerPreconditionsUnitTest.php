<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagerPreconditions;
use PhpTuf\ComposerStager\Tests\Infrastructure\Factory\Translation\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagerPreconditions
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
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditionsInterface|\Prophecy\Prophecy\ObjectProphecy $commonPreconditions
 * @property \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReadyInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDirIsReady
 */
final class StagerPreconditionsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->stagingDirIsReady = $this->prophesize(StagingDirIsReadyInterface::class);
        $this->commonPreconditions
            ->getLeaves()
            ->willReturn([$this->commonPreconditions]);
        $this->stagingDirIsReady
            ->getLeaves()
            ->willReturn([$this->stagingDirIsReady]);

        parent::setUp();
    }

    protected function createSut(): StagerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new StagerPreconditions($commonPreconditions, $stagingDirIsReady, $translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsReady
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The preconditions for staging Composer commands are fulfilled.');
    }

    public function testUnfulfilled(): void
    {
        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->commonPreconditions
            ->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage($this->activeDir, $this->stagingDir, $this->exclusions));
        self::assertTranslatableException(function () use ($sut) {
            $sut->assertIsFulfilled($this->activeDir, $this->stagingDir, $this->exclusions);
        }, PreconditionException::class);
    }
}
