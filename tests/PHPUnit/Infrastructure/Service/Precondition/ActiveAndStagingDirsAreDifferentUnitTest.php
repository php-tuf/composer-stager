<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent
 *
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class ActiveAndStagingDirsAreDifferentUnitTest extends PreconditionTestCase
{
    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        return new ActiveAndStagingDirsAreDifferent();
    }

    public function testFulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->activeDir
            ->resolve()
            ->shouldBeCalledTimes(2)
            ->willReturn('/one/different');
        $this->stagingDir
            ->resolve()
            ->shouldBeCalledTimes(2)
            ->willReturn('/two/different');

        parent::testFulfilled();
    }

    public function testUnfulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $this->activeDir
            ->resolve()
            ->shouldBeCalledTimes(2)
            ->willReturn('/same');
        $this->stagingDir
            ->resolve()
            ->shouldBeCalledTimes(2)
            ->willReturn('/same');

        parent::testUnfulfilled();
    }
}
