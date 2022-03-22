<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Service\ProcessOutputCallback\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 *
 * @covers ::__construct
 * @covers ::getRelativePath
 * @covers ::isDescendant
 * @covers ::sync
 * @covers \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath::getcwd
 *
 * @uses \PhpTuf\ComposerStager\Domain\Exception\DirectoryNotFoundException
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PathException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $rsync
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

    /** @dataProvider providerSync */
    public function testSync($source, $destination, $exclusions, $command, $callback): void
    {
        $source = PathFactory::create($source);
        $destination = PathFactory::create($destination);

        $this->filesystem
            ->mkdir($destination->resolve())
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
            'Siblings: no exclusions given' => [
                'source' => 'source/one',
                'destination' => 'destination/two',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    PathFactory::create('source/one')->resolve() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/two')->resolve(),
                ],
                'callback' => null,
            ],
            'Siblings: simple exclusions given' => [
                'source' => 'source/two' . DIRECTORY_SEPARATOR,
                'destination' => 'destination/two',
                'exclusions' => new PathList([
                    'three.txt',
                    'four.txt',
                ]),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=three.txt',
                    '--exclude=four.txt',
                    PathFactory::create('source/two')->resolve() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/two')->resolve(),
                ],
                'callback' => new TestProcessOutputCallback(),
            ],
            'Siblings: duplicate exclusions given' => [
                'source' => 'source/three',
                'destination' => 'destination/three',
                'exclusions' => new PathList([
                    'four/five',
                    'six/seven',
                    'six/seven',
                    'six/seven',
                ]),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=four/five',
                    '--exclude=six/seven',
                    PathFactory::create('source/three')->resolve() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination/three')->resolve(),
                ],
                'callback' => null,
            ],
            'Nested: destination inside source (neither is excluded)' => [
                'source' => 'source',
                'destination' => 'source/destination',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    PathFactory::create('source')->resolve() . DIRECTORY_SEPARATOR,
                    PathFactory::create('source/destination')->resolve(),
                ],
                'callback' => null,
            ],
            'Nested: source inside destination (source is excluded)' => [
                'source' => 'destination/source',
                'destination' => 'destination',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    // This is the only case where the source directory needs to be excluded.
                    '--exclude=source',
                    PathFactory::create('destination/source')->resolve() . DIRECTORY_SEPARATOR,
                    PathFactory::create('destination')->resolve(),
                ],
                'callback' => null,
            ],
        ];
    }

    /** @dataProvider providerSyncFailure */
    public function testSyncFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

        $source = PathFactory::create('source');
        $destination = PathFactory::create('destination');
        $sut->sync($source, $destination);
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
            ->mkdir(PathFactory::create('destination')->resolve())
            ->willThrow(IOException::class);

        $sut = $this->createSut();

        $source = PathFactory::create('source');
        $destination = PathFactory::create('destination');
        $sut->sync($source, $destination);
    }
}
