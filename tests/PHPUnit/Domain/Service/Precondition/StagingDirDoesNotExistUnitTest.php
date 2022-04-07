<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExist;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExist
 *
 * @covers \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExist::__construct
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 */
final class StagingDirDoesNotExistUnitTest extends TestCase
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
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    protected function createSut(): StagingDirDoesNotExist
    {
        $filesystem = $this->filesystem->reveal();
        return new StagingDirDoesNotExist($filesystem);
    }

    /** @covers ::isFulfilled*/
    public function testIsFulfilled(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir->resolve())
            ->willReturn(false);
        $sut = $this->createSut();

        self::assertEquals(true, $sut->isFulfilled($activeDir, $stagingDir));
    }

    /**
     * @covers ::__construct
     * @covers ::isFulfilled
     */
    public function testIsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir->resolve())
            ->willReturn(true);

        $sut = $this->createSut();

        self::assertFalse($sut->isFulfilled($activeDir, $stagingDir));

        $sut->assertIsFulfilled($activeDir, $stagingDir);
    }
}
