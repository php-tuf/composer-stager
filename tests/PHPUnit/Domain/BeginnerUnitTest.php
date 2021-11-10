<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Beginner;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Beginner
 * @covers \PhpTuf\ComposerStager\Domain\Beginner::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy fileSyncer
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 */
class BeginnerUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(self::ACTIVE_DIR)
            ->willReturn(true);
        $this->filesystem
            ->exists(self::STAGING_DIR)
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
        $activeDir = 'one/two';
        $stagingDir = 'three/four';
        $this->filesystem
            ->exists($activeDir)
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir)
            ->willReturn(false);
        $this->fileSyncer
            ->sync($activeDir, $stagingDir, [], null, 120)
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
                'activeDir' => 'one/two',
                'stagingDir' => 'three/four',
                'givenExclusions' => [],
                'expectedExclusions' => [],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'activeDir' => 'five/six',
                'stagingDir' => 'seven/eight',
                'givenExclusions' => ['nine/ten'],
                'expectedExclusions' => ['nine/ten'],
                'callback' => new TestProcessOutputCallback(),
                'timeout' => 100,
            ],
            [
                'activeDir' => 'eleven/twelve',
                'stagingDir' => 'thirteen/fourteen',
                'givenExclusions' => [
                    'thirteen/fourteen',
                    'fifteen/sixteen',
                ],
                'expectedExclusions' => [
                    'thirteen/fourteen',
                    'fifteen/sixteen',
                ],
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
            ->exists(self::ACTIVE_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->begin(self::ACTIVE_DIR, self::STAGING_DIR);
    }

    /**
     * @covers ::begin
     */
    public function testBeginStagingDirectoryAlreadyExists(): void
    {
        $this->expectException(DirectoryAlreadyExistsException::class);
        $this->expectExceptionMessageMatches('/staging directory already exists/');

        $this->filesystem
            ->exists(self::STAGING_DIR)
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->begin(self::ACTIVE_DIR, self::STAGING_DIR);
    }

    /**
     * @covers ::begin
     */
    public function testIOError(): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->fileSyncer
            ->sync(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willThrow(IOException::class);
        $sut = $this->createSut();

        $sut->begin(self::ACTIVE_DIR, self::STAGING_DIR);
    }
}
