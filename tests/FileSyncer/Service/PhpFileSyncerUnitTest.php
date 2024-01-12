<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use Closure;
use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Environment\Service\EnvironmentInterface;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\API\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\EnvironmentHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\VfsHelper;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 */
final class PhpFileSyncerUnitTest extends TestCase
{
    private EnvironmentInterface|ObjectProphecy $environment;
    private FileFinderInterface|ObjectProphecy $fileFinder;
    private FilesystemInterface|ObjectProphecy $filesystem;
    private PathFactoryInterface|ObjectProphecy $pathFactory;

    protected function setUp(): void
    {
        vfsStream::setup();
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->setTimeLimit(Argument::type('integer'))
            ->willReturn(true);
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->fileExists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
    }

    private function createSut(): PhpFileSyncer
    {
        $environment = $this->environment->reveal();
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new PhpFileSyncer($environment, $fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    /** @covers ::ensureDestinationExists */
    public function testSyncDestinationCouldNotBeCreated(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir(PathHelper::destinationDirPath())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut): void {
            $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());
        }, $previous::class, $message);
    }

    /**
     * @covers ::getRelativePath
     *
     * @dataProvider providerGetRelativePath
     */
    public function testGetRelativePath(string $ancestor, string $path, string $expected): void
    {
        // Expose private method for testing.
        // @see https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
        // @phpstan-ignore-next-line
        $method = static fn (PhpFileSyncer $sut, $ancestor, $path): string => $sut::getRelativePath($ancestor, $path);
        $sut = $this->createSut();
        $method = Closure::bind($method, null, $sut);

        $actual = $method($sut, $ancestor, $path);

        self::assertEquals($expected, $actual);
    }

    /** @phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces */
    public function providerGetRelativePath(): array
    {
        // UNIX-like OS paths.
        if (!EnvironmentHelper::isWindows()) {
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

        // Windows paths.
        return [
            'Match: single directory depth' => [
                'ancestor' => 'One',
                'path'     => 'One\\Two',
                'expected' =>      'Two',
            ],
            'Match: multiple directories depth' => [
                'ancestor' => 'One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' =>           'Three\\Four\\Five',
            ],
            'No match: no shared ancestor' => [
                'ancestor' => 'One\\Two',
                'path'     => 'Three\\Four\\Five\\Six\\Seven',
                'expected' => 'Three\\Four\\Five\\Six\\Seven',
            ],
            'No match: identical paths' => [
                'ancestor' => 'One',
                'path'     => 'One',
                'expected' => 'One',
            ],
            'No match: ancestor only as absolute path' => [
                'ancestor' => '\\One\\Two',
                'path'     => 'One\\Two\\Three\\Four\\Five',
                'expected' => 'One\\Two\\Three\\Four\\Five',
            ],
            'No match: path only as absolute path' => [
                'ancestor' => 'One\\Two',
                'path'     => 'C:\\One\\Two\\Three\\Four',
                'expected' => 'C:\\One\\Two\\Three\\Four',
            ],
            'No match: sneaky "near match"' => [
                'ancestor' => 'One',
                'path'     => 'One_Two',
                'expected' => 'One_Two',
            ],
            'Special case: empty strings' => [
                'ancestor' => '',
                'path'     => '',
                'expected' => '',
            ],
        ];
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyTrue(): void
    {
        $directoryPath = VfsHelper::arbitraryDirPath();
        FilesystemHelper::createDirectories($directoryPath->absolute());
        $sut = $this->createSut();

        $reflection = new ReflectionClass(PhpFileSyncer::class);
        $sut = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isDirEmpty');
        $actual = $method->invoke($sut, $directoryPath);

        self::assertTrue($actual, 'Correctly detected empty directory.');

        FilesystemHelper::remove(PathHelper::testPersistentFixturesAbsolute());
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyFalse(): void
    {
        $directoryPath = VfsHelper::rootDirPath();
        FilesystemHelper::touch(VfsHelper::arbitraryFileAbsolute());
        $sut = $this->createSut();

        $reflection = new ReflectionClass(PhpFileSyncer::class);
        $sut = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isDirEmpty');
        $actual = $method->invoke($sut, $directoryPath);

        self::assertFalse($actual, 'Correctly detected non-empty directory.');
    }

    /**
     * @covers ::isDirEmpty
     *
     * In context, it should be impossible for this method to receive a file,
     * as opposed to a directory, but this documents the theoretical behavior.
     */
    public function testIsDirEmptyNotADirectory(): void
    {
        $filePath = VfsHelper::arbitraryFilePath();
        FilesystemHelper::touch($filePath->absolute());
        $sut = $this->createSut();

        $reflection = new ReflectionClass(PhpFileSyncer::class);
        $sut = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isDirEmpty');
        $actual = $method->invoke($sut, $filePath);

        self::assertFalse($actual, 'Correctly handled non-empty directory.');
    }
}
