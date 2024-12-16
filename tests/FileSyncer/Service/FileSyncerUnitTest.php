<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use Closure;
use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;
use PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface;
use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;
use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\FileSyncer;
use PhpTuf\ComposerStager\Internal\Process\Service\OutputCallback;
use PhpTuf\ComposerStager\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(FileSyncer::class)]
final class FileSyncerUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private ExecutableFinderInterface|ObjectProphecy $executableFinder;
    private FilesystemInterface|ObjectProphecy $filesystem;
    private RsyncProcessRunnerInterface|ObjectProphecy $rsync;

    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->executableFinder = $this->prophesize(ExecutableFinderInterface::class);
        $this->executableFinder
            ->find(Argument::any())
            ->willReturn('/var/bin/executable');
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->filesystem
            ->isDir(Argument::any())
            ->willReturn(true);
        $this->rsync = $this->prophesize(RsyncProcessRunnerInterface::class);

        parent::setUp();
    }

    private function createSut(): FileSyncer
    {
        return new FileSyncer(
            $this->environment->reveal(),
            $this->executableFinder->reveal(),
            $this->filesystem->reveal(),
            self::createPathFactory(),
            self::createPathListFactory(),
            $this->rsync->reveal(),
            self::createTranslatableFactory(),
        );
    }

    #[DataProvider('providerSync')]
    public function testSync(
        string $source,
        string $destination,
        array $optionalArguments,
        array $expectedCommand,
        ?OutputCallbackInterface $expectedCallback,
        int $expectedTimeout,
    ): void {
        $sourcePath = self::createPath($source);
        $destinationPath = self::createPath($destination);

        $this->environment
            ->setTimeLimit($expectedTimeout)
            ->shouldBeCalledOnce();
        $this->filesystem
            ->mkdir($destinationPath)
            ->shouldBeCalledOnce();
        $this->rsync
            ->run($expectedCommand, self::createPath('/', $source), [], $expectedCallback)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync($sourcePath, $destinationPath, ...$optionalArguments);
    }

    public static function providerSync(): array
    {
        return [
            'Minimum arguments' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    'var/www/source/',
                    'var/www/destination',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Simple arguments' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [self::createPathList('one.txt'), new OutputCallback(), 42],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/one.txt',
                    'var/www/source/',
                    'var/www/destination',
                ],
                'expectedCallback' => new OutputCallback(),
                'expectedTimeout' => 42,
            ],
            'Siblings: no exclusions given' => [
                'source' => '/var/www/source/one',
                'destination' => '/var/www/destination/two',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    'var/www/source/one/',
                    'var/www/destination/two',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            ////'Siblings: simple exclusions given' => [
            //'Siblings: simple exclusions given' => [
            'Siblings: simple exclusions given' => [
                'source' => '/var/www/source/two',
                'destination' => '/var/www/destination/two',
                'optionalArguments' => [self::createPathList('three.txt', 'four.txt'), new OutputCallback()],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three.txt',
                    '--exclude=/four.txt',
                    'var/www/source/two/',
                    'var/www/destination/two',
                ],
                'expectedCallback' => new OutputCallback(),
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Siblings: duplicate exclusions given' => [
                'source' => '/var/www/source/three',
                'destination' => '/var/www/destination/three',
                'optionalArguments' => [
                    self::createPathList('four/five', 'six/seven', 'six/seven', 'six/seven'),
                ],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/four/five',
                    '--exclude=/six/seven',
                    'var/www/source/three/',
                    'var/www/destination/three',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Siblings: Windows directory separators' => [
                'source' => '/var/www/source/one\\two',
                'destination' => '/var/www/destination\\one/two',
                'optionalArguments' => [
                    self::createPathList(
                        'three\\four',
                        'five/six/seven/eight',
                        'five/six/seven/eight',
                        'five\\six/seven\\eight',
                        'five/six\\seven/eight',
                    ),
                ],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    '--exclude=/three/four',
                    '--exclude=/five/six/seven/eight',
                    'var/www/source/one/two/',
                    'var/www/destination/one/two',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Nested: destination inside source (neither is excluded)' => [
                'source' => '/var/www/source',
                'destination' => '/var/www/source/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    'var/www/source/',
                    'var/www/source/destination',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Nested: source inside destination (source is excluded)' => [
                'source' => '/var/www/destination/source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    'var/www/destination/source/',
                    'var/www/destination',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
            'Nested: with Windows directory separators' => [
                'source' => '/var/www/destination\\source',
                'destination' => '/var/www/destination',
                'optionalArguments' => [],
                'expectedCommand' => [
                    '--archive',
                    '--checksum',
                    '--delete-after',
                    '--verbose',
                    // "Source inside destination" is the only case where the source directory needs to be excluded.
                    '--exclude=/source',
                    'var/www/destination/source/',
                    'var/www/destination',
                ],
                'expectedCallback' => null,
                'expectedTimeout' => ProcessInterface::DEFAULT_TIMEOUT,
            ],
        ];
    }

    public function testNoRsyncError(): void
    {
        $message = 'Something went wrong.';
        $previous = new LogicException(self::createTranslatableExceptionMessage($message));
        $this->executableFinder->find('rsync')
            ->shouldBeCalledOnce()
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(self::sourceDirPath(), self::destinationDirPath());
        }, LogicException::class, $message);
    }

    #[DataProvider('providerSyncFailure')]
    public function testSyncFailure(ExceptionInterface $caughtException, string $thrownException): void
    {
        $this->rsync
            ->run(Argument::cetera())
            ->willThrow($caughtException);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(self::sourceDirPath(), self::destinationDirPath());
        }, $thrownException, $caughtException->getMessage(), null, $caughtException::class);
    }

    public static function providerSyncFailure(): array
    {
        $message = self::createTranslatableExceptionMessage(__METHOD__);

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

    #[DataProvider('providerGetRelativePath')]
    public function testGetRelativePath(string $ancestor, string $path, string $expected): void
    {
        // Expose private method for testing.
        // @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
        // @phpstan-ignore-next-line
        $method = static fn (FileSyncer $sut, $ancestor, $path): string => $sut::getRelativePath($ancestor, $path);
        $sut = $this->createSut();
        $method = Closure::bind($method, null, $sut);

        $actual = $method($sut, $ancestor, $path);

        self::assertEquals($expected, $actual);
    }

    /** @phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces */
    #[Group('no_windows')]
    public static function providerGetRelativePath(): array
    {
        return [
            'Match: single directory depth' => [
                'ancestor' => 'one',
                'path'     => 'one/two',
                'expected' =>     'two',
            ],
            'Match: multiple directories depth' => [
                'ancestor' => 'one/two',
                'path'     => 'one/two/three/four/five',
                'expected' =>         'three/four/five',
            ],
            'No match: no shared ancestor' => [
                'ancestor' => 'one/two',
                'path'     => 'three/four/five/six/seven',
                'expected' => 'three/four/five/six/seven',
            ],
            'No match: identical paths' => [
                'ancestor' => 'one',
                'path'     => 'one',
                'expected' => 'one',
            ],
            'No match: ancestor only as absolute path' => [
                'ancestor' => '/one/two',
                'path'     => 'one/two/three/four/five',
                'expected' => 'one/two/three/four/five',
            ],
            'No match: path only as absolute path' => [
                'ancestor' => 'one/two',
                'path'     => '/one/two/three/four/five',
                'expected' => '/one/two/three/four/five',
            ],
            'No match: sneaky "near match"' => [
                'ancestor' => 'one',
                'path'     => 'one_two',
                'expected' => 'one_two',
            ],
            'Special case: empty strings' => [
                'ancestor' => '',
                'path'     => '',
                'expected' => '',
            ],
        ];
    }

    public function testSyncCreateDestinationDirectoryFailed(): void
    {
        $message = self::createTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir(self::destinationDirPath())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(self::sourceDirPath(), self::destinationDirPath());
        }, IOException::class, $message);
    }
}
