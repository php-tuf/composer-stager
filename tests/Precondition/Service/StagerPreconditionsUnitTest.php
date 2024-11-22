<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree;
use PhpTuf\ComposerStager\Internal\Precondition\Service\StagerPreconditions;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(AbstractPreconditionsTree::class)]
#[CoversClass(StagerPreconditions::class)]
final class StagerPreconditionsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Stager preconditions';
    protected const DESCRIPTION = 'The preconditions for staging Composer commands.';
    protected const FULFILLED_STATUS_MESSAGE = 'The preconditions for staging Composer commands are fulfilled.';

    private CommonPreconditionsInterface|ObjectProphecy $commonPreconditions;
    private StagingDirIsReadyInterface|ObjectProphecy $stagingDirIsReady;

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
        $environment = $this->environment->reveal();
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new StagerPreconditions($environment, $translatableFactory, $commonPreconditions, $stagingDirIsReady);
    }

    public function testFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $this->commonPreconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsReady
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(
            'The preconditions for staging Composer commands are fulfilled.',
            $activeDirPath,
            $stagingDirPath,
            $timeout,
        );
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->commonPreconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableMessage($message, $sut->getStatusMessage(
            $activeDirPath,
            $stagingDirPath,
            $this->exclusions,
            $timeout,
        ));
        self::assertTranslatableException(function () use ($sut, $activeDirPath, $stagingDirPath, $timeout): void {
            $sut->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout);
        }, PreconditionException::class, $message);
    }
}
