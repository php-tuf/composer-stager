<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirIsReadyInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions
 *
 * @covers ::__construct
 * @covers ::getFulfilledStatusMessage
 */
final class CleanerPreconditionsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Cleaner preconditions';
    protected const DESCRIPTION = 'The preconditions for removing the staging directory.';
    protected const FULFILLED_STATUS_MESSAGE = 'The preconditions for removing the staging directory are fulfilled.';

    private CommonPreconditionsInterface|ObjectProphecy $commonPreconditions;
    private StagingDirIsReadyInterface|ObjectProphecy $stagingDirIsReady;

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
        $environment = $this->environment->reveal();
        $commonPreconditions = $this->commonPreconditions->reveal();
        $stagingDirIsReady = $this->stagingDirIsReady->reveal();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        return new CleanerPreconditions($environment, $commonPreconditions, $stagingDirIsReady, $translatableFactory);
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
        $this->stagingDirIsReady
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(
            'The preconditions for removing the staging directory are fulfilled.',
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
