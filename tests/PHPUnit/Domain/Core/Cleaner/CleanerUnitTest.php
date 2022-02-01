<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core\Cleaner;

use PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner
 * @covers \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner::__construct
 * @uses \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner::directoryExists
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface stagingDir
 */
class CleanerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
    }

    protected function createSut(): Cleaner
    {
        $filesystem = $this->filesystem->reveal();
        return new Cleaner($filesystem);
    }

    /**
     * @covers ::clean
     *
     * @dataProvider providerCleanHappyPath
     */
    public function testCleanHappyPath($path, $callback, $timeout): void
    {
        $path = PathFactory::create($path);
        $this->filesystem
            ->remove($path->resolve(), $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean($path, $callback, $timeout);
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
                'callback' => new TestOutputCallback(),
                'timeout' => 10,
            ],
        ];
    }

    /**
     * @covers ::clean
     */
    public function testCleanDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory.*exist/');

        $this->filesystem
            ->exists($this->stagingDir->resolve())
            ->willReturn(false);
        $this->filesystem
            ->remove($this->stagingDir->resolve())
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->clean($this->stagingDir);
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists($this->stagingDir->resolve())
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists($this->stagingDir);

        self::assertSame($expected, $actual, 'Correctly detected existence of staging directory.');
    }

    public function providerDirectoryExists(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers ::clean
     */
    public function testCleanFailToRemove(): void
    {
        $exception = new IOException();
        $this->expectExceptionObject($exception);
        $this->filesystem
            ->remove($this->stagingDir->resolve(), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->clean($this->stagingDir);
    }
}
