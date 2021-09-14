<?php

namespace PhpTuf\ComposerStager\Tests\Unit\Infrastructure\FileSyncer;

use ArrayIterator;
use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Exception\ProcessFailedException;
use PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\Unit\Domain\TestProcessOutputCallback;
use PhpTuf\ComposerStager\Tests\Unit\TestCase;
use Prophecy\Argument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @covers \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 *
 * @property \PhpTuf\ComposerStager\Infrastructure\Filesystem\FilesystemInterface|\Prophecy\Prophecy\ObjectProphecy filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder $sourceFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder $destinationFinder
 */
class PhpFileSyncerTest extends TestCase
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
     * @dataProvider providerCopyWithOptionalParams
     */
    public function testCopy($source, $givenDestination, $expectedDestination, $givenExclusions, $expectedExclusions, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->fixSeparatorsByReference($givenDestination, $expectedDestination);

        $this->filesystem
            ->mkdir($expectedDestination)
            ->shouldBeCalledOnce();
        $this->sourceFinder
            ->notPath($expectedExclusions)
            ->shouldBeCalled();
        $this->destinationFinder
            ->notPath($expectedExclusions)
            ->shouldBeCalled();
        $sut = $this->createSut();

        $sut->sync($source, $givenDestination, $givenExclusions, $callback, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerCopyWithOptionalParams(): array
    {
        return [
            [
                'source' => 'lorem',
                'givenDestination' => '',
                'expectedDestination' => '',
                'givenExclusions' => null,
                'expectedExclusions' => [''],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'source' => '',
                'givenDestination' => 'lorem',
                'expectedDestination' => 'lorem',
                'givenExclusions' => [],
                'expectedExclusions' => ['lorem'],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'source' => '',
                'givenDestination' => $this->fixSeparators('lorem/ipsum/'),
                'expectedDestination' => $this->fixSeparators('lorem/ipsum'),
                'givenExclusions' => [
                    'amet',
                    'consectetur',
                    $this->fixSeparators('lorem/ipsum'),
                    $this->fixSeparators('lorem/ipsum'),
                    $this->fixSeparators('lorem/ipsum'),
                ],
                'expectedExclusions' => [
                    'amet',
                    'consectetur',
                    $this->fixSeparators('lorem/ipsum'),
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
    public function testCopySourceNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $source = 'source';
        $this->sourceFinder
            ->in($source)
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->sync($source, 'destination', []);
    }

    public function testCopyDestinationCouldNotBeCreated(): void
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
     * @dataProvider providerCopyDeleteFromDestination
     */
    public function testCopyDeleteFromDestination(
        $source,
        $sourceRelativePathname,
        $sourceFilePathname,
        $destination,
        $destinationFilePathname,
        $destinationFileExistsInSource,
        $remove
    ): void {
        $this->fixSeparatorsByReference($source, $sourceRelativePathname, $sourceFilePathname, $destination, $destinationFilePathname);

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

    public function providerCopyDeleteFromDestination(): array
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
     * @dataProvider providerCopyNewFilesToDestination
     */
    public function testCopyNewFilesToDestination(
        $source,
        $sourceFilePathname,
        $destination,
        $destinationRelativePathname,
        $destinationFilePathname,
        $isDir,
        $isFile
    ): void {
        $this->fixSeparatorsByReference($source, $sourceFilePathname, $destination, $destinationRelativePathname, $destinationFilePathname);

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

    public function providerCopyNewFilesToDestination(): array
    {
        return [
            [
                'source' => 'source-lorem',
                'sourceFilePathname' => 'source-lorem/dolor.txt',
                'destination' => 'destination-ipsum',
                'destinationRelativePathname' => 'dolor.txt',
                'destinationFilePathname' => 'destination-ipsum/dolor.txt',
                'isDir' => false,
                'isFile' => true,
            ],
            [
                'source' => 'source-ipsum',
                'sourceFilePathname' => 'source-ipsum/sit',
                'destination' => 'destination-dolor',
                'destinationRelativePathname' => 'sit',
                'destinationFilePathname' => 'destination-dolor/sit',
                'isDir' => true,
                'isFile' => false,
            ],
        ];
    }

    /**
     * @covers ::copyNewFilesToDestination
     */
    public function testCopyNewFilesToDestinationUnknownFileType(): void
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
