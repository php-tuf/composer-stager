<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 *
 * @property \PhpTuf\ComposerStager\API\Translation\Factory\TranslatableFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $translatableFactory
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
        $this->activeDir = new TestPath('/one/different');
        $this->stagingDir = new TestPath('/two/different');

        $this->doTestFulfilled('The active and staging directories are different.');
    }

    /** @covers ::assertIsFulfilled */
    public function testUnfulfilled(): void
    {
        $message = new TestTranslatableExceptionMessage('The active and staging directories are the same.');
        $this->activeDir = new TestPath('/same');
        $this->stagingDir = new TestPath('/same');

        $this->doTestUnfulfilled($message);
    }
}
