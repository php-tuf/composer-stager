<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionTestCase
{
    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        $translatableFactory = new TestTranslatableFactory();

        return new ActiveAndStagingDirsAreDifferent($translatableFactory);
    }

    public function testFulfilled(): void
    {
        $this->doTestFulfilled(
            'The active and staging directories are different.',
            PathHelper::activeDirPath(),
            PathHelper::stagingDirPath(),
        );
    }

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $samePath = PathHelper::activeDirPath();

        $this->doTestUnfulfilled('The active and staging directories are the same.', null, $samePath, $samePath);
    }
}
