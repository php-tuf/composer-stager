<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
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
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface|\Prophecy\Prophecy\ObjectProphecy $rsync
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $destination
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface|\Prophecy\Prophecy\ObjectProphecy $source
 */
final class RsyncFileSyncerUnitTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (self::isRsyncAvailable()) {
            return;
        }

        self::markTestSkipped('Rsync is not available for testing.');
    }

    public function setUp(): void
    {
        $this->source = $this->prophesize(PathInterface::class);
        $this->source
            ->resolve()
            ->willReturn(self::ACTIVE_DIR);
        $this->destination = $this->prophesize(PathInterface::class);
        $this->destination
            ->resolve()
            ->willReturn(self::STAGING_DIR);
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
        $this->source
            ->resolve()
            ->willReturn($source);
        $source = $this->source->reveal();
        $this->destination
            ->resolve()
            ->willReturn($destination);
        $destination = $this->destination->reveal();

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
                    'source/one' . DIRECTORY_SEPARATOR,
                    'destination/two',
                ],
                'callback' => null,
            ],
            'Siblings: simple exclusions given' => [
                'source' => 'source/two',
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
                    'source/two' . DIRECTORY_SEPARATOR,
                    'destination/two',
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
                    'source/three' . DIRECTORY_SEPARATOR,
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
                    'source' . DIRECTORY_SEPARATOR,
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
                    '--exclude=source',
                    'destination/source' . DIRECTORY_SEPARATOR,
                    'destination',
                ],
                'callback' => null,
            ],
        ];
    }

    /** @dataProvider providerSyncFailure */
    public function testSyncFailure($exception): void
    {
        $this->expectException(ProcessFailedException::class);

        $source = $this->source->reveal();
        $destination = $this->destination->reveal();
        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($exception);
        $sut = $this->createSut();

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
        $source = $this->source->reveal();
        $destination = $this->destination->reveal();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The source directory does not exist at "%s"', $source->resolve()));

        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(false);
        $sut = $this->createSut();

        $sut->sync($source, $destination);
    }

    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $this->expectException(IOException::class);

        $source = $this->source->reveal();
        $destination = $this->destination->reveal();
        $this->filesystem
            ->mkdir($destination->resolve())
            ->willThrow(IOException::class);
        $sut = $this->createSut();

        $sut->sync($source, $destination);
    }
}
