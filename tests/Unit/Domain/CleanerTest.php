<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Cleaner;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Cleaner
 * @covers \PhpTuf\ComposerStager\Domain\Cleaner::__construct
 * @uses \PhpTuf\ComposerStager\Domain\Cleaner::directoryExists
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class CleanerTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(static::STAGING_DIR_DEFAULT)
            ->willReturn(true);
    }

    protected function createSut(): Cleaner
    {
        $filesystem = $this->filesystem->reveal();
        return new Cleaner($filesystem);
    }

    /**
     * @covers ::clean
     */
    public function testCleanHappyPath(): void
    {
        $this->filesystem
            ->remove(static::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR_DEFAULT);
    }

    /**
     * @covers ::clean
     */
    public function testCleanDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/staging directory.*exist/');

        $this->filesystem
            ->exists(static::STAGING_DIR_DEFAULT)
            ->willReturn(false);
        $this->filesystem
            ->remove(static::STAGING_DIR_DEFAULT)
            ->shouldNotBeCalled();
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR_DEFAULT);
    }

    /**
     * @covers ::directoryExists
     *
     * @dataProvider providerDirectoryExists
     */
    public function testDirectoryExists($expected): void
    {
        $this->filesystem
            ->exists(static::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $actual = $sut->directoryExists(static::STAGING_DIR_DEFAULT);

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
            ->remove(Argument::any())
            ->shouldBeCalledOnce()
            ->willThrow($exception);
        $sut = $this->createSut();

        $sut->clean(static::STAGING_DIR_DEFAULT);
    }
}
