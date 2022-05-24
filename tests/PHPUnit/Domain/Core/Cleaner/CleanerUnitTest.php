<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Cleaner;

use PhpTuf\ComposerStager\Domain\Aggregate\PreconditionsTree\CleanerPreconditionsInterface;
use PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner
 *
 * @covers \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner::__construct
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CleanerPreconditions|\Prophecy\Prophecy\ObjectProphecy $preconditions
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $stagingDir
 */
final class CleanerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->activeDir = $this->prophesize(PathInterface::class);
        $this->activeDir
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->stagingDir = $this->prophesize(PathInterface::class);
        $this->stagingDir
            ->resolve()
            ->willReturn(self::STAGING_DIR);
        $this->preconditions = $this->prophesize(CleanerPreconditionsInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
    }

    protected function createSut(): Cleaner
    {
        $filesystem = $this->filesystem->reveal();
        $preconditions = $this->preconditions->reveal();

        return new Cleaner($filesystem, $preconditions);
    }

    /**
     * @covers ::clean
     *
     * @dataProvider providerCleanHappyPath
     */
    public function testCleanHappyPath($path, $callback, $timeout): void
    {
        $this->activeDir
            ->resolve()
            ->willReturn($path);
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->filesystem
            ->remove($stagingDir, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean($activeDir, $stagingDir, $callback, $timeout);
    }

    public function providerCleanHappyPath(): array
    {
        return [
            [
                'path' => '/one/two',
                'callback' => null,
                'timeout' => null,
            ],
            [
                'path' => 'three/four',
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /** @covers ::clean */
    public function testCleanPreconditionsUnfulfilled(): void
    {
        $this->expectException(PreconditionException::class);

        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();
        $this->preconditions
            ->assertIsFulfilled($activeDir, $stagingDir)
            ->shouldBeCalledOnce()
            ->willThrow(PreconditionException::class);
        $sut = $this->createSut();

        $sut->clean($activeDir, $stagingDir);
    }

    /** @covers ::clean */
    public function testCleanFailToRemove(): void
    {
        $activeDir = $this->activeDir->reveal();
        $stagingDir = $this->stagingDir->reveal();

        $this->expectException(RuntimeException::class);
        $this->filesystem
            ->remove($stagingDir, Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(IOException::class);
        $sut = $this->createSut();

        $sut->clean($activeDir, $stagingDir);
    }
}
