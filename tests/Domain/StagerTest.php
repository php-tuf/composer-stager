<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Domain\Stager;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\DirectoryNotWritableException;
use PhpTuf\ComposerStager\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Stager
 * @covers ::__construct
 * @covers ::stage
 * @covers ::validatePreconditions
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class StagerTest extends TestCase
{
    private const STAGING_DIR = '/lorem/ipsum';

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->willReturn(true);
        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->willReturn(true);
    }

    public function createSut(): Stager
    {
        $filesystem = $this->filesystem->reveal();
        return new Stager($filesystem);
    }

    public function testSuccess(): void
    {
        $sut = $this->createSut();

        $sut->stage(static::STAGING_DIR);

        self::assertTrue(true, 'Completed correctly.');
    }

    public function testMissingStagingDirectory(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectDeprecationMessageMatches('/.*staging.*exist.*/');

        $this->filesystem
            ->exists(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage(static::STAGING_DIR);
    }

    public function testNonWritableStagingDirectory(): void
    {
        $this->expectException(DirectoryNotWritableException::class);
        $this->expectDeprecationMessageMatches('/.*staging.*writable.*/');

        $this->filesystem
            ->isWritable(static::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->stage(static::STAGING_DIR);
    }
}
