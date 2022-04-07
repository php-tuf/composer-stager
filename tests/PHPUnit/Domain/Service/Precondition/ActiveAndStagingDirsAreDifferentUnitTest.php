<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferent;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferent
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
class ActiveAndStagingDirsAreDifferentUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
    }

    protected function createSut(): ActiveAndStagingDirsAreDifferent
    {
        return new ActiveAndStagingDirsAreDifferent();
    }

    /** @covers ::isFulfilled */
    public function testIsFulfilled(): void
    {
        $this->activeDir
            ->resolve()
            ->willReturn('/one/different');
        $activeDir = $this->activeDir->reveal();
        $this->stagingDir
            ->resolve()
            ->willReturn('/two/different');
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        self::assertEquals(true, $sut->isFulfilled($activeDir, $stagingDir));
    }

    /**
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $this->activeDir
            ->resolve()
            ->willReturn('/same');
        $activeDir = $this->activeDir->reveal();
        $this->stagingDir
            ->resolve()
            ->willReturn('/same');
        $stagingDir = $this->stagingDir->reveal();
        $sut = $this->createSut();

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
