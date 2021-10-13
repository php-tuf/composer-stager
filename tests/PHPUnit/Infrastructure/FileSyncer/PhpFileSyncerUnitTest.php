<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer;

use ArrayIterator;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @covers \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder sourceFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder destinationFinder
 */
class PhpFileSyncerUnitTest extends TestCase
{
    public function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->filesystem
            ->mkdir(Argument::any());
        $this->filesystem
            ->exists(Argument::any())
            ->willReturn(true);
        $this->sourceFinder = $this->prophesize(Finder::class);
        $this->sourceFinder
            ->in(Argument::any())
            ->willReturn($this->sourceFinder);
        $this->sourceFinder
            ->notPath(Argument::any())
            ->willReturn($this->sourceFinder);
        $this->sourceFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([]));
        $this->destinationFinder = $this->prophesize(Finder::class);
        $this->destinationFinder
            ->in(Argument::any())
            ->willReturn($this->destinationFinder);
        $this->destinationFinder
            ->notPath(Argument::any())
            ->willReturn($this->destinationFinder);
        $this->destinationFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([]));
    }

    protected function createSut(): PhpFileSyncer
    {
        $filesystem = $this->filesystem->reveal();
        $sourceFinder = $this->sourceFinder->reveal();
        $destinationFinder = $this->destinationFinder->reveal();
        return new PhpFileSyncer($filesystem, $sourceFinder, $destinationFinder);
    }

    /**
     * @dataProvider providerSyncWithOptionalParams
     */
    public function testSyncWithOptionalParams(
        $givenSource,
        $expectedSource,
        $shouldExcludeSource,
        $givenDestination,
        $expectedDestination,
        $shouldExcludeDestination,
        $givenExclusions,
        $expectedExclusions,
        $callback,
        $givenTimeout,
        $expectedTimeout
    ): void {
        self::fixSeparatorsMultiple($expectedSource, $givenDestination, $expectedDestination);

        $this->filesystem
            ->mkdir($givenDestination)
            ->shouldBeCalledOnce();
        $this->sourceFinder
            ->notPath($expectedExclusions)
            ->shouldBeCalledOnce()
            ->willReturn($this->sourceFinder);
        $this->sourceFinder
            ->notPath($expectedDestination)
            ->shouldBeCalledTimes((int) $shouldExcludeDestination)
            ->willReturn($this->sourceFinder);
        $this->destinationFinder
            ->notPath($expectedExclusions)
            ->shouldBeCalledOnce()
            ->willReturn($this->destinationFinder);
        $this->destinationFinder
            ->notPath($expectedSource)
            ->shouldBeCalledTimes((int) $shouldExcludeSource)
            ->willReturn($this->destinationFinder);
        $sut = $this->createSut();

        $sut->sync($givenSource, $givenDestination, $givenExclusions, $callback, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerSyncWithOptionalParams(): array
    {
        return [
            [
                'givenSource' => 'lorem',
                'expectedSource' => 'lorem/',
                'shouldExcludeSource' => true,
                'givenDestination' => '',
                'expectedDestination' => './',
                'shouldExcludeDestination' => true,
                'givenExclusions' => null,
                'expectedExclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenSource' => 'lorem',
                'expectedSource' => 'lorem/',
                'shouldExcludeSource' => true,
                'givenDestination' => '.',
                'expectedDestination' => './',
                'shouldExcludeDestination' => true,
                'givenExclusions' => null,
                'expectedExclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenSource' => '/lorem',
                'expectedSource' => 'n/a',
                'shouldExcludeSource' => false,
                'givenDestination' => '/ipsum',
                'expectedDestination' => 'n/a',
                'shouldExcludeDestination' => false,
                'givenExclusions' => null,
                'expectedExclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenSource' => '',
                'expectedSource' => './',
                'shouldExcludeSource' => true,
                'givenDestination' => 'lorem',
                'expectedDestination' => 'lorem/',
                'shouldExcludeDestination' => true,
                'givenExclusions' => [],
                'expectedExclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenSource' => '',
                'expectedSource' => './',
                'shouldExcludeSource' => true,
                'givenDestination' => 'lorem/ipsum',
                'expectedDestination' => 'lorem/ipsum/',
                'shouldExcludeDestination' => true,
                'givenExclusions' => [
                    'amet',
                    'consectetur',
                    // Ensure that exclusions get de-duped.
                    'amet',
                    'consectetur',
                    'consectetur',
                ],
                'expectedExclusions' => [
                    'amet',
                    'consectetur',
                ],
                'callback' => new TestProcessOutputCallback(),
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /**
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     */
    public function testSyncSourceNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $source = 'source';
        $this->sourceFinder
            ->in($source)
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->sync($source, 'destination', []);
    }

    public function testSyncDestinationCouldNotBeCreated(): void
    {
        $this->expectException(ProcessFailedException::class);

        $destination = 'source';
        $this->destinationFinder
            ->in($destination)
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->sync('source', $destination, []);
    }

    /**
     * @dataProvider providerDeleteFromDestination
     */
    public function testSyncDeleteFromDestination(
        $source,
        $sourceRelativePathname,
        $sourceFilePathname,
        $destination,
        $destinationFilePathname,
        $destinationFileExistsInSource,
        $remove
    ): void {
        self::fixSeparatorsMultiple($source, $sourceRelativePathname, $sourceFilePathname, $destination, $destinationFilePathname);

        $destinationFileInfo = new SplFileInfo($destinationFilePathname, $destination, $sourceRelativePathname);
        $this->destinationFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$destinationFileInfo]));
        $this->filesystem
            ->exists($sourceFilePathname)
            ->shouldBeCalledOnce()
            ->willReturn($destinationFileExistsInSource);
        $this->filesystem
            ->remove($destinationFilePathname)
            ->shouldBeCalledTimes((int) $remove);

        $sut = $this->createSut();

        $sut->sync($source, $destination);
    }

    public function providerDeleteFromDestination(): array
    {
        return [
            [
                'source' => 'source-lorem',
                'sourceRelativePathname' => 'present.txt',
                'sourceFilePathname' => 'source-lorem/present.txt',
                'destination' => 'destination-ipsum',
                'destinationFilePathname' => 'destination-ipsum/present.txt',
                'destinationFileExistsInSource' => true,
                'remove' => false,
            ],
            [
                'source' => 'source-ipsum',
                'sourceRelativePathname' => 'absent.txt',
                'sourceFilePathname' => 'source-ipsum/absent.txt',
                'destination' => 'destination-dolor',
                'destinationFilePathname' => 'destination-dolor/absent.txt',
                'destinationFileExistsInSource' => false,
                'remove' => true,
            ],
            [
                'source' => '.',
                'sourceRelativePathname' => 'present.txt',
                'sourceFilePathname' => './present.txt',
                'destination' => '.composer_staging',
                'destinationFilePathname' => '.composer_staging/present.txt',
                'destinationFileExistsInSource' => true,
                'remove' => false,
            ],
        ];
    }

    /**
     * @covers ::copyNewFilesToDestination
     *
     * @dataProvider providerNewFilesToDestination
     */
    public function testSyncNewFilesToDestination(
        $source,
        $sourceFilePathname,
        $destination,
        $destinationRelativePathname,
        $destinationFilePathname,
        $isDir,
        $isFile
    ): void {
        self::fixSeparatorsMultiple($source, $sourceFilePathname, $destination, $destinationRelativePathname, $destinationFilePathname);

        $sourceFileInfo = new SplFileInfo($sourceFilePathname, $source, $destinationRelativePathname);
        $this->sourceFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$sourceFileInfo]));
        $this->filesystem
            ->isDir($sourceFilePathname)
            ->willReturn($isDir);
        $this->filesystem
            ->mkdir($destinationFilePathname)
            ->shouldBeCalledTimes((int) $isDir);
        $this->filesystem
            ->isFile($sourceFilePathname)
            ->willReturn($isFile);
        $this->filesystem
            ->copy($sourceFilePathname, $destinationFilePathname)
            ->shouldBeCalledTimes((int) $isFile);

        $sut = $this->createSut();

        $sut->sync($source, $destination);
    }

    public function providerNewFilesToDestination(): array
    {
        return [
            [
                'source' => 'source1',
                'sourceFilePathname' => 'source1/file.txt',
                'destination' => 'destination1',
                'destinationRelativePathname' => 'file.txt',
                'destinationFilePathname' => 'destination1/file.txt',
                'isDir' => false,
                'isFile' => true,
            ],
            [
                'source' => 'source2',
                'sourceFilePathname' => 'source2/dir',
                'destination' => 'destination2',
                'destinationRelativePathname' => 'dir',
                'destinationFilePathname' => 'destination2/dir',
                'isDir' => true,
                'isFile' => false,
            ],
        ];
    }

    /**
     * @covers ::copyNewFilesToDestination
     */
    public function testSyncNewFilesToDestinationUnknownFileType(): void
    {
        $this->expectException(ProcessFailedException::class);

        $source = 'source';
        $sourceFilePathname = 'source/unknown_type';
        $destinationRelativePathname = 'unknown_type';
        $destinationFilePathname = 'destination/unknown_type';

        $sourceFileInfo = new SplFileInfo($sourceFilePathname, $source, $destinationRelativePathname);
        $this->sourceFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$sourceFileInfo]));

        $this->filesystem
            ->isDir($sourceFilePathname)
            ->willReturn(false);
        $this->filesystem
            ->mkdir($destinationFilePathname)
            ->shouldNotBeCalled();
        $this->filesystem
            ->isFile($sourceFilePathname)
            ->willReturn(false);
        $this->filesystem
            ->copy($sourceFilePathname, $destinationFilePathname)
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $sut->sync($source, 'destination');
    }
}
