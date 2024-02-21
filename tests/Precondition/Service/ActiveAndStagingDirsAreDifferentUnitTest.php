<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\TestDoubles\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent
 *
 * @covers \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition::getStatusMessage
 */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionUnitTestCase
{
    protected const NAME = 'Active and staging directories are different';
    protected const DESCRIPTION = 'The active and staging directories cannot be the same.';
    protected const FULFILLED_STATUS_MESSAGE = 'The active and staging directories are different.';

    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        $environment = $this->environment->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new ActiveAndStagingDirsAreDifferent($environment, $translatableFactory);
    }

    /**
     * @covers ::doAssertIsFulfilled
     * @covers ::getFulfilledStatusMessage
     */
    public function testFulfilled(): void
    {
        $this->doTestFulfilled(
            'The active and staging directories are different.',
            PathTestHelper::activeDirPath(),
            PathTestHelper::stagingDirPath(),
        );
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The active and staging directories are the same.';
        $samePath = PathTestHelper::activeDirPath();

        $this->doTestUnfulfilled($message, null, $samePath, $samePath);
    }
}
