<?php

namespace PhpTuf\ComposerStager\Tests\Domain;

use PhpTuf\ComposerStager\Console\Application;
use PhpTuf\ComposerStager\Domain\Beginner;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Beginner
 * @covers ::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier|\Prophecy\Prophecy\ObjectProphecy fileCopier
 */
class BeginnerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileCopier = $this->prophesize(FileCopier::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->filesystem
            ->exists(Application::ACTIVE_DIR_DEFAULT)
            ->willReturn(true);
        $this->filesystem
            ->exists(Application::STAGING_DIR_DEFAULT)
            ->willReturn(false);
    }

    private function createSut(): Beginner
    {
        $fileCopier = $this->fileCopier->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Beginner($fileCopier, $filesystem);
    }

    /**
     * @covers ::activeDirectoryExists
     * @covers ::begin
     * @covers ::stagingDirectoryExists
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
                'callback' => static function () {
                }
            ],
        ];
    }

    /**
     * @covers ::activeDirectoryExists
     * @covers ::begin
     */
    public function testBeginActiveDirectoryDoesNotExist(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessageMatches('/active directory.*not exist/');

        $this->filesystem
            ->exists(Application::ACTIVE_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->begin(Application::ACTIVE_DIR_DEFAULT, Application::STAGING_DIR_DEFAULT);
    }

    /**
     * @covers ::activeDirectoryExists
     * @covers ::begin
     * @covers ::stagingDirectoryExists
     */
    public function testBeginStagingDirectoryAlreadyExists(): void
    {
        $this->expectException(DirectoryAlreadyExistsException::class);
        $this->expectExceptionMessageMatches('/staging directory already exists/');

        $this->filesystem
            ->exists(Application::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->begin(Application::ACTIVE_DIR_DEFAULT, Application::STAGING_DIR_DEFAULT);
    }

    /**
     * @covers ::activeDirectoryExists
     * @covers ::stagingDirectoryExists
     *
     * @dataProvider providerDirectoryExistsMethods
     */
    public function testDirectoryExistsMethods($expected): void
    {
        $this->filesystem
            ->exists(Application::ACTIVE_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $this->filesystem
            ->exists(Application::STAGING_DIR_DEFAULT)
            ->shouldBeCalledOnce()
            ->willReturn($expected);
        $sut = $this->createSut();

        $activeDir = $sut->activeDirectoryExists(Application::ACTIVE_DIR_DEFAULT);
        $stagingDir = $sut->stagingDirectoryExists(Application::STAGING_DIR_DEFAULT);

        self::assertSame($expected, $activeDir, 'Correctly detected presence of active directory.');
        self::assertSame($expected, $stagingDir, 'Correctly detected presence of staging directory.');
    }

    public function providerDirectoryExistsMethods(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
