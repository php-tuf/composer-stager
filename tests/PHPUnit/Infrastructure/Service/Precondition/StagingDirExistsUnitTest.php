<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\Precondition;

use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists
 *
 * @covers ::__construct
 * @covers ::assertIsFulfilled
 * @covers ::isFulfilled
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class StagingDirExistsUnitTest extends PreconditionTestCase
{
    protected function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);

        parent::setUp();
    }

    protected function createSut(): StagingDirExists
    {
        $filesystem = $this->filesystem->reveal();

        return new StagingDirExists($filesystem);
    }

    public function testFulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir)
            ->shouldBeCalledTimes(2)
            ->willReturn(true);

        parent::testFulfilled();
    }

    public function testUnfulfilled(): void
    {
        // Double expectations: once for ::isFulfilled() and once for ::assertIsFulfilled().
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->exists($stagingDir)
            ->shouldBeCalledTimes(2)
            ->willReturn(false);

        parent::testUnfulfilled();
    }
}
