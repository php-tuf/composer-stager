<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use AssertionError;
use Closure;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface;
use PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface;
use PhpTuf\ComposerStager\Internal\Host\Service\Host;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Tests\Path\Value\TestPath;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory;
use PhpTuf\ComposerStager\Tests\Translation\Value\TestTranslatableExceptionMessage;
use Prophecy\Argument;
use ReflectionClass;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\TranslatableExceptionTrait
 * @uses \PhpTuf\ComposerStager\API\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 *
 * @property \PhpTuf\ComposerStager\Internal\Filesystem\Service\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @property \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinderInterface|\Prophecy\Prophecy\ObjectProphecy $fileFinder
 * @property \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactoryInterface|\Prophecy\Prophecy\ObjectProphecy $pathFactory
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $source
 * @property \PhpTuf\ComposerStager\Tests\Translation\Factory\TestTranslatableFactory $translatableFactory
 */
final class PhpFileSyncerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->source = new TestPath(self::ACTIVE_DIR);
        $this->destination = new TestPath(self::STAGING_DIR);
        $this->fileFinder = $this->prophesize(FileFinderInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->filesystem
            ->isDirEmpty(Argument::any())
            ->willReturn(false);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
    }

    private function createSut(): PhpFileSyncer
    {
        $fileFinder = $this->fileFinder->reveal();
        $filesystem = $this->filesystem->reveal();
        $pathFactory = $this->pathFactory->reveal();
        $translatableFactory = new TestTranslatableFactory();

        return new PhpFileSyncer($fileFinder, $filesystem, $pathFactory, $translatableFactory);
    }

    public function testSyncSourceNotFound(): void
    {
        $this->filesystem
            ->exists($this->source)
            ->willReturn(false);
        $sut = $this->createSut();

        $message = sprintf('The source directory does not exist at %s', $this->source->resolved());
        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
        }, LogicException::class, $message);
    }

    public function testSyncDirectoriesTheSame(): void
    {
        $same = new TestPath('same');
        $sut = $this->createSut();

        $message = sprintf('The source and destination directories cannot be the same at %s', $same->resolved());
        self::assertTranslatableException(static function () use ($sut, $same) {
            $sut->sync($same, $same);
        }, LogicException::class, $message);
    }

    /** @covers ::ensureDestinationExists */
    public function testSyncDestinationCouldNotBeCreated(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->filesystem
            ->mkdir($this->destination)
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
        }, $previous::class, $message);
    }

    public function testSyncCouldNotFindDestination(): void
    {
        $message = new TestTranslatableExceptionMessage(__METHOD__);
        $previous = new IOException($message);
        $this->fileFinder
            ->find($this->destination, Argument::any())
            ->willThrow($previous);
        $sut = $this->createSut();

        self::assertTranslatableException(function () use ($sut) {
            $sut->sync($this->source, $this->destination);
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

    /** phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces */
    public function providerGetRelativePath(): array
    {
        // UNIX-like OS paths.
        if (!Host::isWindows()) {
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

    /**
     * @covers ::assertSourceAndDestinationAreDifferent
     * @covers ::assertSourceExists
     */
    public function testTransMissingTranslatableFactory(): void
    {
        self::assertTranslatableException(function () {
            $same = new TestPath('same');
            $source = $same;
            $destination = $same;
            $sut = $this->createSut();

            $reflection = new ReflectionClass($sut);
            $sut = $reflection->newInstanceWithoutConstructor();
            $translatableFactory = $reflection->getProperty('translatableFactory');
            $translatableFactory->setValue($sut, null);

            $sut->sync($source, $destination);
        }, AssertionError::class, 'The "p()" method requires a translatable factory. '
            . 'Provide one by calling "setTranslatableFactory()" in the constructor.');
    }
}
