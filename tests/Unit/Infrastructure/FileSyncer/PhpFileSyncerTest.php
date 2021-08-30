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
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder fromFinder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder toFinder
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
        $this->fromFinder = $this->prophesize(Finder::class);
        $this->fromFinder
            ->in(Argument::any())
            ->willReturn($this->fromFinder);
        $this->fromFinder
            ->notPath(Argument::any())
            ->willReturn($this->fromFinder);
        $this->fromFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([]));
        $this->toFinder = $this->prophesize(Finder::class);
        $this->toFinder
            ->in(Argument::any())
            ->willReturn($this->toFinder);
        $this->toFinder
            ->notPath(Argument::any())
            ->willReturn($this->toFinder);
        $this->toFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([]));
    }

    protected function createSut(): PhpFileSyncer
    {
        $filesystem = $this->filesystem->reveal();
        $fromIterator = $this->fromFinder->reveal();
        $toIterator = $this->toFinder->reveal();
        return new PhpFileSyncer($filesystem, $fromIterator, $toIterator);
    }

    /**
     * @dataProvider providerCopyWithOptionalParams
     */
    public function testCopy($givenTo, $expectedTo, $exclusions, $callback, $givenTimeout, $expectedTimeout): void
    {
        $this->fixSeparators($givenTo, $expectedTo);

        $this->filesystem
            ->mkdir($expectedTo)
            ->shouldBeCalledOnce();
        $sut = $this->createSut();

        $sut->sync('from', $givenTo, $exclusions, $callback, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerCopyWithOptionalParams(): array
    {
        return [
            [
                'givenTo' => '',
                'expectedTo' => '',
                'exclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenTo' => 'lorem',
                'expectedTo' => 'lorem',
                'exclusions' => [],
                'callback' => null,
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenTo' => 'ipsum/dolor/',
                'expectedTo' => 'ipsum/dolor',
                'exclusions' => [
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
    public function testCopyFromDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $from = 'from';
        $this->fromFinder
            ->in($from)
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->sync($from, 'to', []);
    }

    public function testCopyToDirectoryCouldNotBeCreated(): void
    {
        $this->expectException(ProcessFailedException::class);

        $to = 'to';
        $this->toFinder
            ->in($to)
            ->willThrow(\Symfony\Component\Finder\Exception\DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->sync('from', $to, []);
    }

    /**
     * @dataProvider providerCopyDeleteFromDestination
     */
    public function testCopyDeleteFromDestination(
        $fromDir,
        $fromRelativePathname,
        $fromFilePathname,
        $toDir,
        $toFilePathname,
        $toFileExistsInFrom,
        $remove
    ): void {
        $this->fixSeparators($fromDir, $fromRelativePathname, $fromFilePathname, $toDir, $toFilePathname);

        $toFileInfo = new SplFileInfo($toFilePathname, $toDir, $fromRelativePathname);
        $this->toFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$toFileInfo]));
        $this->filesystem
            ->exists($fromFilePathname)
            ->shouldBeCalledOnce()
            ->willReturn($toFileExistsInFrom);
        $this->filesystem
            ->remove($toFilePathname)
            ->shouldBeCalledTimes((int) $remove);

        $sut = $this->createSut();

        $sut->sync($fromDir, $toDir);
    }

    public function providerCopyDeleteFromDestination(): array
    {
        return [
            [
                'fromDir' => 'from-lorem',
                'fromRelativePathname' => 'present.txt',
                'fromFilePathname' => 'from-lorem/present.txt',
                'toDir' => 'to-ipsum',
                'toFilePathname' => 'to-ipsum/present.txt',
                'toFileExistsInFrom' => true,
                'remove' => false,
            ],
            [
                'fromDir' => 'from-ipsum',
                'fromRelativePathname' => 'absent.txt',
                'fromFilePathname' => 'from-ipsum/absent.txt',
                'toDir' => 'to-dolor',
                'toFilePathname' => 'to-dolor/absent.txt',
                'toFileExistsInFrom' => false,
                'remove' => true,
            ],
            [
                'fromDir' => '.',
                'fromRelativePathname' => 'present.txt',
                'fromFilePathname' => './present.txt',
                'toDir' => '.composer_staging',
                'toFilePathname' => '.composer_staging/present.txt',
                'toFileExistsInFrom' => true,
                'remove' => false,
            ],
        ];
    }

    /**
     * @covers ::copyNewFilesToToDirectory
     *
     * @dataProvider providerCopyNewFilesToToDirectory
     */
    public function testCopyNewFilesToToDirectory(
        $fromDir,
        $fromFilePathname,
        $toDir,
        $toRelativePathname,
        $toFilePathname,
        $isDir,
        $isFile
    ): void {
        $this->fixSeparators($fromDir, $fromFilePathname, $toDir, $toRelativePathname, $toFilePathname);

        $fromFileInfo = new SplFileInfo($fromFilePathname, $fromDir, $toRelativePathname);
        $this->fromFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$fromFileInfo]));
        $this->filesystem
            ->isDir($fromFilePathname)
            ->willReturn($isDir);
        $this->filesystem
            ->mkdir($toFilePathname)
            ->shouldBeCalledTimes((int) $isDir);
        $this->filesystem
            ->isFile($fromFilePathname)
            ->willReturn($isFile);
        $this->filesystem
            ->copy($fromFilePathname, $toFilePathname)
            ->shouldBeCalledTimes((int) $isFile);

        $sut = $this->createSut();

        $sut->sync($fromDir, $toDir);
    }

    public function providerCopyNewFilesToToDirectory(): array
    {
        return [
            [
                'fromDir' => 'from-lorem',
                'fromFilePathname' => 'from-lorem/dolor.txt',
                'toDir' => 'to-ipsum',
                'toRelativePathname' => 'dolor.txt',
                'toFilePathname' => 'to-ipsum/dolor.txt',
                'isDir' => false,
                'isFile' => true,
            ],
            [
                'fromDir' => 'from-ipsum',
                'fromFilePathname' => 'from-ipsum/sit.txt',
                'toDir' => 'to-dolor',
                'toRelativePathname' => 'dolor.txt',
                'toFilePathname' => 'to-dolor/sit.txt',
                'isDir' => true,
                'isFile' => false,
            ],
        ];
    }

    /**
     * @covers ::copyNewFilesToToDirectory
     */
    public function testCopyNewFilesToToDirectoryUnknownFileType(): void
    {
        $this->expectException(ProcessFailedException::class);

        $fromDir = 'from';
        $fromFilePathname = 'from/unknown_type';
        $toDir = 'to';
        $toRelativePathname = 'unknown_type';
        $toFilePathname = 'to/unknown_type';

        $fromFileInfo = new SplFileInfo($fromFilePathname, $fromDir, $toRelativePathname);
        $this->fromFinder
            ->getIterator()
            ->willReturn(new ArrayIterator([$fromFileInfo]));

        $this->filesystem
            ->isDir($fromFilePathname)
            ->willReturn(false);
        $this->filesystem
            ->mkdir($toFilePathname)
            ->shouldNotBeCalled();
        $this->filesystem
            ->isFile($fromFilePathname)
            ->willReturn(false);
        $this->filesystem
            ->copy($fromFilePathname, $toFilePathname)
            ->shouldNotBeCalled();

        $sut = $this->createSut();

        $sut->sync($fromDir, $toDir);
    }
}
