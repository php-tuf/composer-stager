<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain;

use PhpTuf\ComposerStager\Domain\Beginner;
use PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Domain\Beginner
 * @covers \PhpTuf\ComposerStager\Domain\Beginner::__construct
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 *
 * @property \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface|\Prophecy\Prophecy\ObjectProphecy fileSyncer
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface activeDir
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface stagingDir
 */
class BeginnerUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->activeDir = PathFactory::create(self::ACTIVE_DIR);
        $this->stagingDir = PathFactory::create(self::STAGING_DIR);
        $this->fileSyncer = $this->prophesize(FileSyncerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists($this->activeDir->getResolved())
            ->willReturn(true);
        $this->filesystem
            ->exists($this->stagingDir->getResolved())
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
        $this->filesystem
            ->exists($this->activeDir->getResolved())
            ->willReturn(true);
        $this->filesystem
            ->exists($this->stagingDir->getResolved())
            ->willReturn(false);
        $this->fileSyncer
            ->sync($this->activeDir, $this->stagingDir, [], null, 120)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::begin
     *
     * @dataProvider providerBeginWithOptionalParams
     */
    public function testBeginWithOptionalParams($activeDir, $stagingDir, $givenExclusions, $expectedExclusions, $callback, $timeout): void
    {
        $activeDir = PathFactory::create($activeDir);
        $stagingDir = PathFactory::create($stagingDir);

        $this->filesystem
            ->exists($activeDir->getResolved())
            ->willReturn(true);
        $this->filesystem
            ->exists($stagingDir->getResolved())
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
                'givenExclusions' => null,
                'expectedExclusions' => [],
                'callback' => null,
                'timeout' => null,
            ],
            [
                'activeDir' => 'five/six',
                'stagingDir' => 'seven/eight',
                'givenExclusions' => PathAggregateFactory::create(['nine/ten']),
                'expectedExclusions' => [PathFactory::create('nine/ten')->getResolved()],
                'callback' => new TestOutputCallback(),
                'timeout' => 100,
            ],
            [
                'activeDir' => 'eleven/twelve',
                'stagingDir' => 'thirteen/fourteen',
                'givenExclusions' => PathAggregateFactory::create([
                    'thirteen/fourteen',
                    'fifteen/sixteen',
                ]),
                'expectedExclusions' => [
                    PathFactory::create('thirteen/fourteen')->getResolved(),
                    PathFactory::create('fifteen/sixteen')->getResolved(),
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
            ->exists($this->activeDir->getResolved())
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
    }

    /**
     * @covers ::begin
     */
    public function testBeginStagingDirectoryAlreadyExists(): void
    {
        $this->expectException(DirectoryAlreadyExistsException::class);
        $this->expectExceptionMessageMatches('/staging directory already exists/');

        $this->filesystem
            ->exists($this->stagingDir->getResolved())
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $sut = $this->createSut();

        $sut->begin($this->activeDir, $this->stagingDir);
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

        $sut->begin($this->activeDir, $this->stagingDir);
    }
}
