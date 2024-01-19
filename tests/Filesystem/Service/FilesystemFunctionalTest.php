<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\VfsHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem */
final class FilesystemFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup();
        FilesystemHelper::createDirectories([
            PathHelper::sourceDirAbsolute(),
            PathHelper::destinationDirAbsolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        return ContainerHelper::get(Filesystem::class);
    }

    /**
     * @covers ::assertCopyPreconditions
     * @covers ::copy
     */
    public function testCopy(): void
    {
        $mode = 0775;
        $contents = 'Arbitrary file contents';
        vfsStream::create(['source.txt' => $contents]);
        $sourceFilePath = VfsHelper::createPath('source.txt');
        $sourceFileAbsolute = $sourceFilePath->absolute();
        $destinationFilePath = VfsHelper::createPath('some/arbitrary/depth/destination.txt');
        $destinationFileAbsolute = $destinationFilePath->absolute();
        assert(FilesystemHelper::fileMode($sourceFileAbsolute) !== $mode, 'The new file already has the same permissions as will be set.');
        FilesystemHelper::chmod($sourceFileAbsolute, $mode);
        $sut = $this->createSut();

        $sut->copy($sourceFilePath, $destinationFilePath);

        self::assertVfsStructureIsSame([
            'source.txt' => $contents,
            'some' => [
                'arbitrary' => [
                    'depth' => ['destination.txt' => $contents],
                ],
            ],
        ]);
        self::assertFileMode($destinationFileAbsolute, $mode);
    }

    /**
     * @covers ::assertCopyPreconditions
     * @covers ::copy
     */
    public function testCopySourceFileNotFound(): void
    {
        $sourceFilePath = VfsHelper::nonExistentFilePath();
        $destinationFilePath = VfsHelper::arbitraryFilePath();
        $sut = $this->createSut();

        $message = sprintf('The source file does not exist at %s', $sourceFilePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $sourceFilePath, $destinationFilePath): void {
            $sut->copy($sourceFilePath, $destinationFilePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::assertCopyPreconditions
     * @covers ::copy
     */
    public function testCopySourceIsADirectory(): void
    {
        $fileRelative = PathHelper::arbitraryFileRelative();
        $sourceFilePath = PathHelper::createPath($fileRelative, PathHelper::sourceDirAbsolute());
        $destinationFilePath = PathHelper::createPath($fileRelative, PathHelper::destinationDirAbsolute());
        FilesystemHelper::createDirectories($sourceFilePath->absolute());
        $sut = $this->createSut();

        $message = sprintf('The source cannot be a directory at %s', $sourceFilePath->absolute());
        self::assertTranslatableException(static function () use ($sut, $sourceFilePath, $destinationFilePath): void {
            $sut->copy($sourceFilePath, $destinationFilePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::copy
     *
     * @group no_windows
     */
    public function testCopyFailure(): void
    {
        $fileRelative = PathHelper::arbitraryFileRelative();
        $sourceFilePath = PathHelper::createPath($fileRelative, PathHelper::sourceDirAbsolute());
        // Try to copy the file to the directory root, which should always fail.
        $destinationFilePath = PathHelper::createPath($fileRelative, '/');
        FilesystemHelper::touch($sourceFilePath->absolute());
        $sut = $this->createSut();

        // Get expected error details.
        @copy($sourceFilePath->absolute(), $destinationFilePath->absolute());
        $details = error_get_last()['message'];

        $message = sprintf('Failed to copy %s to %s: %s', $sourceFilePath->absolute(), $destinationFilePath->absolute(), $details);
        self::assertTranslatableException(static function () use ($sut, $sourceFilePath, $destinationFilePath): void {
            $sut->copy($sourceFilePath, $destinationFilePath);
        }, IOException::class, $message);
    }

    /**
     * @covers ::chmod
     *
     * @group no_windows
     */
    public function testChmod(): void
    {
        $mode = 0775;
        $filePath = VfsHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemHelper::touch($fileAbsolute);
        $sut = $this->createSut();

        $sut->chmod($filePath, $mode);

        self::assertFileMode($fileAbsolute, $mode);
    }

    /**
     * @covers ::chmod
     *
     * @group no_windows
     */
    public function testChmodFileDoesNotExist(): void
    {
        $path = VfsHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The file cannot be found at %s.', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->chmod($path, 0777);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::fileMode
     *
     * @dataProvider providerFileMode
     *
     * @group no_windows
     */
    public function testFileModeOnFile(int $mode): void
    {
        $filePath = VfsHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemHelper::touch($fileAbsolute);
        FilesystemHelper::chmod($fileAbsolute, $mode);
        $sut = $this->createSut();

        $actual = $sut->fileMode($filePath);

        self::assertSame($mode, $actual, 'Got correct permissions.');
    }

    /**
     * @covers ::fileMode
     *
     * @dataProvider providerFileMode
     *
     * @group no_windows
     */
    public function testFileModeOnDirectory(int $mode): void
    {
        $directoryPath = VfsHelper::arbitraryDirPath();
        $directoryAbsolute = $directoryPath->absolute();
        FilesystemHelper::createDirectories($directoryAbsolute);
        FilesystemHelper::chmod($directoryAbsolute, $mode);
        $sut = $this->createSut();

        $actual = $sut->fileMode($directoryPath);

        self::assertSame($mode, $actual, 'Got correct permissions.');
    }

    public function providerFileMode(): array
    {
        return [
            [0644],
            [0775],
            [0777],
        ];
    }

    /** @covers ::fileMode */
    public function testFileModeFileDoesNotExist(): void
    {
        $path = VfsHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('No such file: %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->fileMode($path);
        }, LogicException::class, $message);
    }

    /**
     * This tests the code examples in the ::fileMode() docblock.
     *
     * @coversNothing
     *
     * @see \PhpTuf\ComposerStager\API\Filesystem\Service\FilesystemInterface::fileMode
     *
     * @group no_windows
     */
    public function testFileModeDocblockExamples(): void
    {
        $filePath = VfsHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemHelper::touch($fileAbsolute);
        $filesystem = $this->createSut();

        // Begin first example code.
        chmod($fileAbsolute, 0644);
        $mode = $filesystem->fileMode($filePath);
        assert($mode === 0644); // true
        // End first example code.

        self::assertSame(0644, $mode, 'The first code example works.');

        // Begin second example code.
        chmod($fileAbsolute, 0644);
        $mode = $filesystem->fileMode($filePath);
        $mode = substr(sprintf('0%o', $mode), -4);
        assert($mode === "0644"); // true
        // End second example code.

        self::assertSame("0644", $mode, 'The second code example works.');
    }

    /**
     * @covers ::fileExists
     * @covers ::getFileType
     * @covers ::isDir
     * @covers ::isFile
     * @covers ::isHardLink
     * @covers ::isLink
     * @covers ::isSymlink
     *
     * @dataProvider providerTypeCheckMethods
     */
    public function testTypeCheckMethods(
        array $files,
        array $directories,
        array $symlinks,
        array $hardLinks,
        string $subject,
        bool $exists,
        bool $isDir,
        bool $isFile,
        bool $isLink,
        bool $isHardLink,
        bool $isSymlink,
    ): void {
        self::createFiles(PathHelper::sourceDirAbsolute(), $files);
        FilesystemHelper::createDirectories($directories, PathHelper::sourceDirAbsolute());
        FilesystemHelper::createSymlinks(PathHelper::sourceDirAbsolute(), $symlinks);
        FilesystemHelper::createHardlinks(PathHelper::sourceDirAbsolute(), $hardLinks);
        $subject = PathHelper::createPath($subject, PathHelper::sourceDirAbsolute());
        $sut = $this->createSut();

        $actualExists = $sut->fileExists($subject);
        $actualIsDir = $sut->isDir($subject);
        $actualIsFile = $sut->isFile($subject);
        $actualIsLink = $sut->isLink($subject);
        $actualIsHardLink = $sut->isHardLink($subject);
        $actualIsSymlink = $sut->isSymlink($subject);

        self::assertSame($exists, $actualExists, 'Correctly determined whether path exists.');
        self::assertSame($isDir, $actualIsDir, 'Correctly determined whether path is a directory.');
        self::assertSame($isFile, $actualIsFile, 'Correctly determined whether path is a regular file.');
        self::assertSame($isLink, $actualIsLink, 'Correctly determined whether path is a link.');
        self::assertSame($isHardLink, $actualIsHardLink, 'Correctly determined whether path is a hard link.');
        self::assertSame($isSymlink, $actualIsSymlink, 'Correctly determined whether path is a symlink.');
    }

    public function providerTypeCheckMethods(): array
    {
        return [
            'Path is a symlink to a file' => [
                'files' => ['target.txt'],
                'directories' => [],
                'symlinks' => ['symlink.txt' => 'target.txt'],
                'hardLinks' => [],
                'subject' => 'symlink.txt',
                'exists' => true,
                'isDir' => false,
                'isFile' => false,
                'isLink' => true,
                'isHardLink' => false,
                'isSymlink' => true,
            ],
            'Path is a symlink to a directory' => [
                'files' => [],
                'directories' => ['target_directory'],
                'symlinks' => ['directory_link' => 'target_directory'],
                'hardLinks' => [],
                'subject' => 'directory_link',
                'exists' => true,
                'isDir' => false,
                'isFile' => false,
                'isLink' => true,
                'isHardLink' => false,
                'isSymlink' => true,
            ],
            // Creating a hard link to a directory is not a permitted
            // operation. Just test with a file.
            'Path is a hard link to a file' => [
                'files' => ['target.txt'],
                'directories' => [],
                'symlinks' => [],
                'hardLinks' => ['hard_link.txt' => 'target.txt'],
                'subject' => 'hard_link.txt',
                'exists' => true,
                'isDir' => false,
                'isFile' => false,
                'isLink' => true,
                'isHardLink' => true,
                'isSymlink' => false,
            ],
            'Path is a regular file' => [
                'files' => ['file.txt'],
                'directories' => [],
                'symlinks' => [],
                'hardLinks' => [],
                'subject' => 'file.txt',
                'exists' => true,
                'isDir' => false,
                'isFile' => true,
                'isLink' => false,
                'isHardLink' => false,
                'isSymlink' => false,
            ],
            'Path is a directory' => [
                'files' => [],
                'directories' => ['directory'],
                'symlinks' => [],
                'hardLinks' => [],
                'subject' => 'directory',
                'exists' => true,
                'isDir' => true,
                'isFile' => false,
                'isLink' => false,
                'isHardLink' => false,
                'isSymlink' => false,
            ],
            'Path does not exist' => [
                'files' => [],
                'directories' => [],
                'symlinks' => [],
                'hardLinks' => [],
                'subject' => PathHelper::nonExistentFileRelative(),
                'exists' => false,
                'isDir' => false,
                'isFile' => false,
                'isLink' => false,
                'isHardLink' => false,
                'isSymlink' => false,
            ],
        ];
    }

    /**
     * @covers ::readLink
     *
     * @dataProvider providerReadlink
     */
    public function testReadlink(string $given, string $expectedAbsolute): void
    {
        $basePath = PathHelper::sourceDirAbsolute();
        $symlinkPath = PathHelper::createPath('symlink.txt', $basePath);
        $hardLinkPath = PathHelper::createPath('hard_link.txt', $basePath);
        $targetPath = PathHelper::createPath($given, $basePath);
        chdir($basePath);
        FilesystemHelper::touch($targetPath->absolute());
        symlink($given, $symlinkPath->absolute());
        link($given, $hardLinkPath->absolute());
        $sut = $this->createSut();

        // Change directory to make sure the result isn't affected by the CWD at runtime.
        chdir(__DIR__);

        $symlinkTarget = $sut->readLink($symlinkPath);

        self::assertEquals($expectedAbsolute, $symlinkTarget->absolute(), 'Got the correct absolute target value.');

        $message = sprintf('The path does not exist or is not a symlink at %s', $hardLinkPath->absolute());
        self::assertTranslatableException(static function () use ($sut, $hardLinkPath): void {
            $sut->readLink($hardLinkPath);
        }, IOException::class, $message);
    }

    public function providerReadlink(): array
    {
        $basePath = PathHelper::sourceDirAbsolute();
        $absolute = static fn ($path): string => PathHelper::makeAbsolute($path, $basePath);

        return [
            'Absolute link' => [
                'given' => $absolute('target.txt'),
                'expectedAbsolute' => $absolute('target.txt'),
            ],
            'Relative link' => [
                'given' => 'target.txt',
                'expectedAbsolute' => $absolute('target.txt'),
            ],
        ];
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonLink(): void
    {
        $file = PathHelper::createPath(__FILE__);
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a symlink at %s', $file->absolute());
        self::assertTranslatableException(static function () use ($sut, $file): void {
            $sut->readLink($file);
        }, IOException::class, $message);
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonExistentFile(): void
    {
        $path = PathHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a symlink at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->readLink($path);
        }, IOException::class, $message);
    }

    /** @covers ::touch */
    public function testTouch(): void
    {
        $path = VfsHelper::arbitraryFilePath();
        $sut = $this->createSut();

        $sut->touch($path);

        self::assertFileExists($path->absolute());
    }

    /** @covers ::touch */
    public function testTouchAlreadyADirectory(): void
    {
        $directoryPath = VfsHelper::arbitraryDirPath();
        $directoryAbsolute = $directoryPath->absolute();
        FilesystemHelper::createDirectories([$directoryAbsolute]);
        $sut = $this->createSut();

        $message = sprintf('Cannot touch file--a directory already exists at %s', $directoryAbsolute);
        self::assertTranslatableException(static function () use ($sut, $directoryPath): void {
            $sut->touch($directoryPath);
        }, LogicException::class, $message);
    }
}
