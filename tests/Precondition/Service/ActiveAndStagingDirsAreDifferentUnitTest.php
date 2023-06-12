<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Service\TestTranslator;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveAndStagingDirsAreDifferent
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::getFulfilledStatusMessage
 * @covers ::getStatusMessage
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableAwareTrait
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 *
 * @property \PhpTuf\ComposerStager\Domain\Translation\Factory\TranslatableFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $translatableFactory
 */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionTestCase
{
    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        $translatableFactory = new TestTranslatableFactory();
        $translator = new TestTranslator();

        return new ActiveAndStagingDirsAreDifferent($translatableFactory, $translator);
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
