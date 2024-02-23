<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CommitterPreconditions;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\CommitterPreconditions
 *
 * @covers ::__construct
 * @covers ::getFulfilledStatusMessage
 */
final class CommitterPreconditionsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Committer preconditions';
    protected const DESCRIPTION = 'The preconditions for making staged changes live.';
    protected const FULFILLED_STATUS_MESSAGE = 'The preconditions for making staged changes live are fulfilled.';

    private CommonPreconditionsInterface|ObjectProphecy $commonPreconditions;
    private NoUnsupportedLinksExistInterface|ObjectProphecy $noUnsupportedLinksExist;
    private StagingDirIsReadyInterface|ObjectProphecy $stagingDirIsReady;

    protected function setUp(): void
    {
        $this->commonPreconditions = $this->prophesize(CommonPreconditionsInterface::class);
        $this->commonPreconditions
            ->getLeaves()
            ->willReturn([$this->commonPreconditions]);
        $this->noUnsupportedLinksExist = $this->prophesize(NoUnsupportedLinksExistInterface::class);
        $this->noUnsupportedLinksExist
            ->getLeaves()
            ->willReturn([$this->noUnsupportedLinksExist]);
        $this->stagingDirIsReady = $this->prophesize(StagingDirIsReadyInterface::class);
        $this->stagingDirIsReady
            ->getLeaves()
            ->willReturn([$this->stagingDirIsReady]);

        parent::setUp();
    }

    protected function createSut(): CommitterPreconditions
    {
        $environment = $this->environment->reveal();
        $commonPreconditions = $this->commonPreconditions->reveal();
        $noUnsupportedLinksExist = $this->noUnsupportedLinksExist->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = self::createTranslatableFactory();

        return new CommitterPreconditions($environment, $commonPreconditions, $noUnsupportedLinksExist, $stagingDirIsReady, $translatableFactory);
    }

    /** @covers ::getFulfilledStatusMessage */
    public function testFulfilled(): void
    {
        $activeDirPath = self::activeDirPath();
        $stagingDirPath = self::stagingDirPath();
        $timeout = 42;

        $this->commonPreconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noUnsupportedLinksExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirIsReady
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(
            'The preconditions for making staged changes live are fulfilled.',
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

        $this->doTestUnfulfilled($message, null, $activeDirPath, $stagingDirPath, $timeout);
    }
}
