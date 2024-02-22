<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Precondition\Service\CommonPreconditionsInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\NoUnsupportedLinksExistInterface;
use PhpTuf\ComposerStager\API\Precondition\Service\StagingDirDoesNotExistInterface;
use PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\TranslationTestHelper;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions
 *
 * @covers ::__construct
 * @covers ::getFulfilledStatusMessage
 */
final class BeginnerPreconditionsUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Beginner preconditions';
    protected const DESCRIPTION = 'The preconditions for beginning the staging process.';
    protected const FULFILLED_STATUS_MESSAGE = 'The preconditions for beginning the staging process are fulfilled.';

    private CommonPreconditionsInterface|ObjectProphecy $commonPreconditions;
    private NoUnsupportedLinksExistInterface|ObjectProphecy $noUnsupportedLinksExist;
    private StagingDirDoesNotExistInterface|ObjectProphecy $stagingDirDoesNotExist;

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
        $this->stagingDirDoesNotExist = $this->prophesize(StagingDirDoesNotExistInterface::class);
        $this->stagingDirDoesNotExist
            ->getLeaves()
            ->willReturn([$this->stagingDirDoesNotExist]);

        parent::setUp();
    }

    protected function createSut(): BeginnerPreconditions
    {
        $commonPreconditions = $this->commonPreconditions->reveal();
        $environment = $this->environment->reveal();
        $noUnsupportedLinksExist = $this->noUnsupportedLinksExist->reveal();
        $stagingDirDoesNotExist = $this->stagingDirDoesNotExist->reveal();
        $translatableFactory = TranslationTestHelper::createTranslatableFactory();

        return new BeginnerPreconditions(
            $environment,
            $commonPreconditions,
            $noUnsupportedLinksExist,
            $stagingDirDoesNotExist,
            $translatableFactory,
        );
    }

    /** @covers ::getFulfilledStatusMessage */
    public function testFulfilled(): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();
        $timeout = 42;

        $this->commonPreconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->noUnsupportedLinksExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);
        $this->stagingDirDoesNotExist
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->shouldBeCalledTimes(self::EXPECTED_CALLS_MULTIPLE);

        $this->doTestFulfilled(
            'The preconditions for beginning the staging process are fulfilled.',
            $activeDirPath,
            $stagingDirPath,
            $timeout,
        );
    }

    public function testUnfulfilled(): void
    {
        $activeDirPath = PathTestHelper::activeDirPath();
        $stagingDirPath = PathTestHelper::stagingDirPath();
        $timeout = 42;

        $message = __METHOD__;
        $previous = self::createTestPreconditionException($message);
        $this->commonPreconditions
            ->assertIsFulfilled($activeDirPath, $stagingDirPath, $this->exclusions, $timeout)
            ->willThrow($previous);

        $this->doTestUnfulfilled($message, null, $activeDirPath, $stagingDirPath, $timeout);
    }
}
