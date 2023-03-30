<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent
 *
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::getUnfulfilledStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionTestCase
{
    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        return new ActiveAndStagingDirsAreDifferent();
    }

    public function testFulfilled(): void
    {
        $this->activeDir = new TestPath('/one/different');
        $this->stagingDir = new TestPath('/two/different');

        $this->doTestFulfilled('The active and staging directories are different.');
    }

    public function testUnfulfilled(): void
    {
        $this->activeDir = new TestPath('/same');
        $this->stagingDir = new TestPath('/same');

        $this->doTestUnfulfilled('The active and staging directories are the same.');
    }
}
