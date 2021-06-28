<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Beginner;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopierInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Beginner
 * @covers ::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopierInterface|\Prophecy\Prophecy\ObjectProphecy fileCopier
 */
class BeginnerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileCopier = $this->prophesize(FileCopierInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(self::ACTIVE_DIR_DEFAULT)
            ->willReturn(true);
        $this->filesystem
            ->exists(self::STAGING_DIR_DEFAULT)
            ->willReturn(false);
    }

    private function createSut(): Beginner
    {
        $fileCopier = $this->fileCopier->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Beginner($fileCopier, $filesystem);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerBeginHappyPath
     */
    public function testBeginHappyPath($activeDir, $stagingDir, $callback): void
    {
        $this->filesystem
            ->exists($activeDir)
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(false);
        $exclusions = [
            $stagingDir,
            '.git',
        ];
        $this->fileCopier
            ->copy($activeDir, $stagingDir, $exclusions, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir, $callback);
    }

    public function providerBeginHappyPath(): array
    {
        return [
            [
                'activeDir' => 'lorem/ipsum',
                'stagingDir' => 'dolor/sit',
                'callback' => null,
            ],
            [
                'activeDir' => 'dolor/sit',
                'stagingDir' => 'lorem/ipsum',
                'callback' => new TestProcessOutputCallback(),
            ],
        ];
    }

    /**
     * @covers ::begin
     */
    public function testBeginActiveDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/active directory.*not exist/');

        $this->filesystem
            ->exists(self::ACTIVE_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->begin(self::ACTIVE_DIR_DEFAULT, self::STAGING_DIR_DEFAULT);
    }

    /**
     * @covers ::begin
     */
    public function testBeginStagingDirectoryAlreadyExists(): void
    {
        $this->expectException(DirectoryAlreadyExistsException::class);
        $this->expectExceptionMessageMatches('/staging directory already exists/');

        $this->filesystem
            ->exists(self::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->begin(self::ACTIVE_DIR_DEFAULT, self::STAGING_DIR_DEFAULT);
    }
}
