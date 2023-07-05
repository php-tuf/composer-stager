<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Path\Value\PathList;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessOutputCallbackInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\Process\Service\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer
 *
 * @covers ::__construct
 * @covers ::assertDirectoriesAreNotTheSame
 * @covers ::assertSourceExists
 * @covers ::buildCommand
 * @covers ::ensureDestinationDirectoryExists
 * @covers ::getRelativePath
 * @covers ::isDescendant
 * @covers ::runCommand
 * @covers ::sync
 *
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Internal\Process\Service\RsyncProcessRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $rsync
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $source
 *
 * @group no_windows
 */
final class RsyncFileSyncerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->source = new TestPath(self::ACTIVE_DIR);
        $this->destination = new TestPath(self::STAGING_DIR);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->rsync = $this->prophesize(RsyncProcessRunnerInterface::class);
    }

    private function createSut(): RsyncFileSyncer
    {
        $filesystem = $this->filesystem->reveal();
        $rsync = $this->rsync->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new RsyncFileSyncer($filesystem, $rsync, $translatableFactory);
    }

    /**
     * @covers ::sync
     *
     * @dataProvider providerSync
     */
    public function testSync(
        string $source,
        string $destination,
        ?PathListInterface $exclusions,
        array $command,
        ?ProcessOutputCallbackInterface $callback,
    ): void {
        $source = new TestPath($source);
        $destination = new TestPath($destination);

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
            'Siblings: no exclusions given' => [
                'source' => 'source/one',
                'destination' => 'destination/two',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    'source/one/',
                    'destination/two',
                ],
                'callback' => null,
            ],
            'Siblings: simple exclusions given' => [
                'source' => 'source/two',
                'destination' => 'destination/two',
                'exclusions' => new PathList('three.txt', 'four.txt'),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three.txt',
                    '--exclude=/four.txt',
                    'source/two/',
                    'destination/two',
                ],
                'callback' => new TestProcessOutputCallback(),
            ],
            'Siblings: duplicate exclusions given' => [
                'source' => 'source/three',
                'destination' => 'destination/three',
                'exclusions' => new PathList(...[
                    'four/five',
                    'six/seven',
                    'six/seven',
                    'six/seven',
                ]),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/four/five',
                    '--exclude=/six/seven',
                    'source/three/',
                    'destination/three',
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
                    'source/',
                    'source/destination',
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
                    '--exclude=/source',
                    'destination/source/',
                    'destination',
                ],
                'callback' => null,
            ],
        ];
    }

    /**
     * @covers ::runCommand
     *
     * @dataProvider providerSyncFailure
     */
    public function testSyncFailure(ExceptionInterface $caught, string $thrown): void
    {
        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($caught);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
        }, $thrown, $caught->getMessage(), $caught::class);
    }

    public function providerSyncFailure(): array
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);

        return [
            [
                'caught' => new LogicException($message),
                'thrown' => IOException::class,
            ],
            [
                'caught' => new RuntimeException($message),
                'thrown' => IOException::class,
            ],
        ];
    }

    /** @covers ::assertSourceExists */
    public function testSyncSourceDirectoryNotFound(): void
    {
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);
        $sut = $this->createSut();

        $message = sprintf('The source directory does not exist at %s', $this->source->resolved());
        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
        }, LogicException::class, $message);
    }

    /** @covers ::assertDirectoriesAreNotTheSame */
    public function testSyncDirectoriesTheSame(): void
    {
        $source = new TestPath('same');
        $destination = $source;
        $sut = $this->createSut();

        $message = sprintf('The source and destination directories cannot be the same at %s', $source->resolved());
        self::assertTranslatableException(static function () use ($sut, $source, $destination) {
            $sut->sync($source, $destination);
        }, LogicException::class, $message);
    }

    /** @covers ::ensureDestinationDirectoryExists */
    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir($this->destination)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
        }, IOException::class, $message);
    }
}
