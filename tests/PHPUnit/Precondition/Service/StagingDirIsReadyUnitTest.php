<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirExistsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsWritableInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsReady;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsReady
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 */
final class StagingDirIsReadyUnitTest extends PreconditionTestCase
{
    private StagingDirExistsInterface|ObjectProphecy $stagingDirExists;
    private StagingDirIsWritableInterface|ObjectProphecy $stagingDirIsWritable;

    protected function setUp(): void
    {
        $this->stagingDirExists = $this->prophesize(StagingDirExistsInterface::class);
        $this->stagingDirIsWritable = $this->prophesize(StagingDirIsWritableInterface::class);
        $this->stagingDirExists
            ->getLeaves()
            ->willReturn([$this->stagingDirExists]);
        $this->stagingDirIsWritable
            ->getLeaves()
            ->willReturn([$this->stagingDirIsWritable]);

        parent::setUp();
    }

    protected function createSut(): StagingDirIsReady
    {
        $stagingDirExists = $this->stagingDirExists->reveal();
        $stagingDirIsWritable = $this->stagingDirIsWritable->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new StagingDirIsReady($translatableFactory, $stagingDirExists, $stagingDirIsWritable);
    }

    public function testFulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $this->stagingDirExists
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsWritable
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled('The staging directory is ready to use.');
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = PathHelper::activeDirPath();
        $stagingDirPath = PathHelper::stagingDirPath();

        $message = 'The staging directory is not ready to use.';
        $previous = self::createTestPreconditionException($message);
        $this->stagingDirExists
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage($activeDirPath, $stagingDirPath, $this->exclusions));
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions);
        }, PreconditionException::class, $message);
    }
}
