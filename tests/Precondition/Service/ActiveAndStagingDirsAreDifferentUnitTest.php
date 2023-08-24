<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionTestCase
{
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
            PathHelper::activeDirPath(),
            PathHelper::stagingDirPath(),
        );
    }

    /** @covers ::doAssertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = 'The active and staging directories are the same.';
        $samePath = PathHelper::activeDirPath();

        $this->doTestUnfulfilled($message, null, $samePath, $samePath);
    }
}
