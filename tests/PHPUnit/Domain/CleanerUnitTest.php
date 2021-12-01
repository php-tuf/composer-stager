<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Cleaner;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Cleaner
 * @covers \PhpTuf\ComposerStager\Domain\Cleaner::__construct
 * @uses \PhpTuf\ComposerStager\Domain\Cleaner::directoryExists
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class CleanerUnitTest extends TestCase
{
    public function setUp(): void
    {
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
        $this->filesystem
            ->remove($path, $callback, $timeout)
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
            ->exists(static::STAGING_DIR)
            ->willReturn(false);
        $this->filesystem
            ->remove(static::STAGING_DIR)
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR, null);
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR);

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
            ->remove(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR, null);
    }
}
