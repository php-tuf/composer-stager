<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Domain;

use PhpTuf\ComposerStager\Domain\Beginner;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncerInterface;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Beginner
 * @covers \PhpTuf\ComposerStager\Domain\Beginner::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy fileSyncer
 */
class BeginnerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileSyncer = $this->prophesize(RsyncFileSyncerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(self::ACTIVE_DIR_DEFAULT)
            ->willReturn(true);
        $this->filesystem
            ->exists(self::STAGING_DIR_DEFAULT)
            ->willReturn(false);
    }

    protected function createSut(): Beginner
    {
        $fileSyncer = $this->fileSyncer->reveal();
        $filesystem = $this->filesystem->reveal();
        return new Beginner($fileSyncer, $filesystem);
    }

    /**
     * @covers ::begin
     */
    public function testBeginWithMinimumParams(): void
    {
        $activeDir = 'lorem/ipsum';
        $stagingDir = 'dolor/sit';
        $this->filesystem
            ->exists($activeDir)
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(false);
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, [$stagingDir], null, 120)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerBeginWithOptionalParams
     */
    public function testBeginWithOptionalParams($activeDir, $stagingDir, $givenExclusions, $expectedExclusions, $callback, $timeout): void
    {
        $this->filesystem
            ->exists($activeDir)
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(false);
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, $expectedExclusions, $callback, $timeout)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($activeDir, $stagingDir, $givenExclusions, $callback, $timeout);
    }

    public function providerBeginWithOptionalParams(): array
    {
        return [
            [
                'activeDir' => 'lorem/ipsum',
                'stagingDir' => 'dolor/sit',
                'givenExclusions' => null,
                'expectedExclusions' => ['dolor/sit'],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'activeDir' => 'dolor/sit',
                'stagingDir' => 'lorem/ipsum',
                'givenExclusions' => ['amet/consectetur'],
                'expectedExclusions' => [
                    'amet/consectetur',
                    'lorem/ipsum',
                ],
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 100,
            ],
            [
                'activeDir' => 'sit/amet',
                'stagingDir' => 'amet/consectetur',
                'givenExclusions' => ['amet/consectetur'],
                'expectedExclusions' => ['amet/consectetur'],
                'callback' => null,
                'timeout' => null,
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
