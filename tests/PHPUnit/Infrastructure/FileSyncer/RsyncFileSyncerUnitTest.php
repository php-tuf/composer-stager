<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Domain\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Util\PathUtil
 *
 * @property \PhpTuf\ComposerStager\Domain\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Domain\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
 */
class RsyncFileSyncerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->rsync = $this->prophesize(RsyncRunnerInterface::class);
    }

    protected function createSut(): RsyncFileSyncer
    {
        $filesystem = $this->filesystem->reveal();
        $rsync = $this->rsync->reveal();
        return new RsyncFileSyncer($filesystem, $rsync);
    }

    /**
     * @dataProvider providerSync
     */
    public function testSync($source, $destination, $exclusions, $command, $callback): void
    {
        $source = PathFactory::create($source);
        $destination = PathFactory::create($destination);

        $this->filesystem
            ->mkdir($destination->getResolved())
            ->shouldBeCalledOnce();
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync($source, $destination, $exclusions, $callback);
    }

    public function providerSync(): array
    {
        return [
            [
                'source' => 'source/one',
                'destination' => 'destination/two',
                'exclusions' => [],
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=' . PathFactory::create('source/one')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('source/one')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/two')->getResolved() . DIRECTORY_SEPARATOR,
                ],
                'callback' => null,
            ],
            [
                'source' => 'source/two' . DIRECTORY_SEPARATOR,
                'destination' => 'destination/two',
                'exclusions' => [
                    'three',
                    'four.txt',
                ],
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=three',
                    '--exclude=four.txt',
                    '--exclude=' . PathFactory::create('source/two')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('source/two')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/two')->getResolved() . DIRECTORY_SEPARATOR,
                ],
                'callback' => new TestOutputCallback(),
            ],
            [
                'source' => 'source/three',
                'destination' => 'destination/three',
                'exclusions' => [
                    'destination/three',
                    'destination/three',
                    'destination/three',
                ],
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=destination/three',
                    '--exclude=' . PathFactory::create('source/three')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('source/three')->getResolved() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/three')->getResolved() . DIRECTORY_SEPARATOR,
                ],
                'callback' => null,
            ],
        ];
    }

    /**
     * @dataProvider providerSyncFailure
     */
    public function testSyncFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $source = PathFactory::create('lorem');
        $destination = PathFactory::create('ipsum');
        $sut->sync($source, $destination, []);
    }

    public function providerSyncFailure(): array
    {
        return [
            [IOException::class],
            [LogicException::class],
            [ProcessFailedException::class],
        ];
    }

    public function testSyncSourceDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);

        $sut = $this->createSut();

        $source = PathFactory::create(self::ACTIVE_DIR);
        $destination = PathFactory::create(self::STAGING_DIR);
        $sut->sync($source, $destination);
    }

    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $this->expectException(IOException::class);

        $this->filesystem
            ->mkdir(PathFactory::create('destination')->getResolved())
            ->willThrow(IOException::class);

        $sut = $this->createSut();

        $source = PathFactory::create('source');
        $destination = PathFactory::create('destination');
        $sut->sync($source, $destination);
    }
}
