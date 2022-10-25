<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer;

use Closure;
use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Exception\LogicException;
use PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Domain\Service\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $source
 */
final class PhpFileSyncerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->source = new TestPath(self::ACTIVE_DIR);
        $this->destination = new TestPath(self::STAGING_DIR);
        $this->fileFinder = $this->prophesize(RecursiveFileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
    }

    protected function createSut(): PhpFileSyncer
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();

        return new PhpFileSyncer($fileFinder, $filesystem, $pathFactory);
    }

    public function testSyncSourceNotFound(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The source directory does not exist at "%s"', $this->source->resolve()));

        $this->filesystem
            ->exists($this->source)
            ->willReturn(false);

        $sut = $this->createSut();

        $sut->sync($this->source, $this->destination);
    }

    public function testSyncDirectoriesTheSame(): void
    {
        $source = new TestPath('same');
        $destination = $source;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('The source and destination directories cannot be the same at "%s"', $source->resolve()));

        $sut = $this->createSut();

        $sut->sync($source, $destination);
    }

    public function testSyncDestinationCouldNotBeCreated(): void
    {
        $this->expectException(IOException::class);

        $this->filesystem
            ->mkdir($this->destination)
            ->willThrow(IOException::class);

        $sut = $this->createSut();

        $sut->sync($this->source, $this->destination);
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

    /** phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces */
    public function providerGetRelativePath(): array
    {
        // UNIX-like OS paths.
        if (!self::isWindows()) {
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
}
