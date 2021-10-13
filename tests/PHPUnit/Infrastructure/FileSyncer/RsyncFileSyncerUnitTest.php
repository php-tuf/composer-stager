<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\IOException;
use PhpTuf\ComposerStager\Exception\LogicException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Process\Runner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy rsync
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
        $this->filesystem
            ->mkdir($destination)
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
                    '--exclude=source/one',
                    'source/one' . DIRECTORY_SEPARATOR,
                    'destination/two' . DIRECTORY_SEPARATOR,
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
                    '--exclude=source/two' . DIRECTORY_SEPARATOR,
                    'source/two' . DIRECTORY_SEPARATOR,
                    'destination/two' . DIRECTORY_SEPARATOR,
                ],
                'callback' => new TestProcessOutputCallback(),
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
                    '--exclude=source/three',
                    'source/three' . DIRECTORY_SEPARATOR,
                    'destination/three' . DIRECTORY_SEPARATOR,
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

        $sut->sync('lorem', 'ipsum', []);
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

        $sut->sync(self::ACTIVE_DIR, self::STAGING_DIR);
    }

    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $this->expectException(IOException::class);

        $this->filesystem
            ->mkdir('destination')
            ->willThrow(IOException::class);

        $sut = $this->createSut();

        $sut->sync('source', 'destination');
    }
}
