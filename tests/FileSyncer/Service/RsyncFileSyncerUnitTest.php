<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Tests\Process\Service\TestOutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer
 *
 * @covers ::__construct
 * @covers ::buildCommand
 * @covers ::ensureDestinationDirectoryExists
 * @covers ::getRelativePath
 * @covers ::runCommand
 * @covers ::sync
 *
 * @group no_windows
 */
final class RsyncFileSyncerUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private FilesystemInterface|ObjectProphecy $filesystem;
    private RsyncProcessRunnerInterface|ObjectProphecy $rsync;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->rsync = $this->prophesize(RsyncProcessRunnerInterface::class);

        parent::setUp();
    }

    private function createSut(): RsyncFileSyncer
    {
        $environment = $this->environment->reveal();
        $filesystem = $this->filesystem->reveal();
        $rsync = $this->rsync->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new RsyncFileSyncer($environment, $filesystem, $rsync, $translatableFactory);
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
        ?OutputCallbackInterface $callback,
    ): void {
        $sourcePath = PathHelper::createPath($source);
        $destinationPath = PathHelper::createPath($destination);

        $this->filesystem
            ->mkdir($destinationPath)
            ->shouldBeCalledOnce();
        $this->rsync
            ->run($command, $callback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync($sourcePath, $destinationPath, $exclusions, $callback);
    }

    public function providerSync(): array
    {
        return [
            'Siblings: no exclusions given' => [
                'source' => '/var/www/source/one',
                'destination' => '/var/www/destination/two',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '/var/www/source/one/',
                    '/var/www/destination/two',
                ],
                'callback' => null,
            ],
            'Siblings: simple exclusions given' => [
                'source' => '/var/www/source/two',
                'destination' => '/var/www/destination/two',
                'exclusions' => new PathList('three.txt', 'four.txt'),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three.txt',
                    '--exclude=/four.txt',
                    '/var/www/source/two/',
                    '/var/www/destination/two',
                ],
                'callback' => new TestOutputCallback(),
            ],
            'Siblings: duplicate exclusions given' => [
                'source' => '/var/www/source/three',
                'destination' => '/var/www/destination/three',
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
                    '/var/www/source/three/',
                    '/var/www/destination/three',
                ],
                'callback' => null,
            ],
            'Siblings: Windows directory separators' => [
                'source' => '/var/www/source/one\\two',
                'destination' => '/var/www/destination\\one/two',
                'exclusions' => new PathList(...[
                    'three\\four',
                    'five/six/seven/eight',
                    'five/six/seven/eight',
                    'five\\six/seven\\eight',
                    'five/six\\seven/eight',
                ]),
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three/four',
                    '--exclude=/five/six/seven/eight',
                    '/var/www/source/one/two/',
                    '/var/www/destination/one/two',
                ],
                'callback' => null,
            ],
            'Nested: destination inside source (neither is excluded)' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/source/destination',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    '/var/www/source/',
                    '/var/www/source/destination',
                ],
                'callback' => null,
            ],
            'Nested: source inside destination (source is excluded)' => [
                'source' => '/var/www/destination/source',
                'destination' => '/var/www/destination',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    '/var/www/destination/source/',
                    '/var/www/destination',
                ],
                'callback' => null,
            ],
            'Nested: with Windows directory separators' => [
                'source' => '/var/www/destination\\source',
                'destination' => '/var/www/destination',
                'exclusions' => null,
                'command' => [
                    '--archive',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    '/var/www/destination/source/',
                    '/var/www/destination',
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
    public function testSyncFailure(ExceptionInterface $caughtException, string $thrownException): void
    {
        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($caughtException);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());
        }, $thrownException, $caughtException->getMessage(), null, $caughtException::class);
    }

    public function providerSyncFailure(): array
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);

        return [
            'LogicException' => [
                'caughtException' => new LogicException($message),
                'thrownException' => IOException::class,
            ],
            'RuntimeException' => [
                'caughtException' => new RuntimeException($message),
                'thrownException' => IOException::class,
            ],
        ];
    }

    /** @covers ::ensureDestinationDirectoryExists */
    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir(PathHelper::destinationDirPath())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());
        }, IOException::class, $message);
    }
}
