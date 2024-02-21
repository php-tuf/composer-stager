<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use org\bovigo\vfs\vfsStream;
use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\VfsTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem */
final class FilesystemFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup();
        FilesystemTestHelper::createDirectories([
            PathTestHelper::sourceDirAbsolute(),
            PathTestHelper::destinationDirAbsolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        return ContainerTestHelper::get(Filesystem::class);
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
        $sourceFilePath = VfsTestHelper::createPath('source.txt');
        $sourceFileAbsolute = $sourceFilePath->absolute();
        $destinationFilePath = VfsTestHelper::createPath('some/arbitrary/depth/destination.txt');
        $destinationFileAbsolute = $destinationFilePath->absolute();
        assert(FilesystemTestHelper::fileMode($sourceFileAbsolute) !== $mode, 'The new file already has the same permissions as will be set.');
        FilesystemTestHelper::chmod($sourceFileAbsolute, $mode);
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
        $sourceFilePath = VfsTestHelper::nonExistentFilePath();
        $destinationFilePath = VfsTestHelper::arbitraryFilePath();
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
        $fileRelative = PathTestHelper::arbitraryFileRelative();
        $sourceFilePath = PathTestHelper::createPath($fileRelative, PathTestHelper::sourceDirAbsolute());
        $destinationFilePath = PathTestHelper::createPath($fileRelative, PathTestHelper::destinationDirAbsolute());
        FilesystemTestHelper::createDirectories($sourceFilePath->absolute());
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
        $fileRelative = PathTestHelper::arbitraryFileRelative();
        $sourceFilePath = PathTestHelper::createPath($fileRelative, PathTestHelper::sourceDirAbsolute());
        // Try to copy the file to the directory root, which should always fail.
        $destinationFilePath = PathTestHelper::createPath($fileRelative, '/');
        FilesystemTestHelper::touch($sourceFilePath->absolute());
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
        $filePath = VfsTestHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemTestHelper::touch($fileAbsolute);
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
        $path = VfsTestHelper::nonExistentFilePath();
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
        $filePath = VfsTestHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemTestHelper::touch($fileAbsolute);
        FilesystemTestHelper::chmod($fileAbsolute, $mode);
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
        $directoryPath = VfsTestHelper::arbitraryDirPath();
        $directoryAbsolute = $directoryPath->absolute();
        FilesystemTestHelper::createDirectories($directoryAbsolute);
        FilesystemTestHelper::chmod($directoryAbsolute, $mode);
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
        $path = VfsTestHelper::nonExistentFilePath();
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
        $filePath = VfsTestHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        FilesystemTestHelper::touch($fileAbsolute);
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
        self::createFiles(PathTestHelper::sourceDirAbsolute(), $files);
        FilesystemTestHelper::createDirectories($directories, PathTestHelper::sourceDirAbsolute());
        FilesystemTestHelper::createSymlinks(PathTestHelper::sourceDirAbsolute(), $symlinks);
        FilesystemTestHelper::createHardlinks(PathTestHelper::sourceDirAbsolute(), $hardLinks);
        $subject = PathTestHelper::createPath($subject, PathTestHelper::sourceDirAbsolute());
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
                'subject' => PathTestHelper::nonExistentFileRelative(),
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
        $basePath = PathTestHelper::sourceDirAbsolute();
        $symlinkPath = PathTestHelper::createPath('symlink.txt', $basePath);
        $hardLinkPath = PathTestHelper::createPath('hard_link.txt', $basePath);
        $targetPath = PathTestHelper::createPath($given, $basePath);
        chdir($basePath);
        FilesystemTestHelper::touch($targetPath->absolute());
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
        $basePath = PathTestHelper::sourceDirAbsolute();
        $absolute = static fn ($path): string => PathTestHelper::makeAbsolute($path, $basePath);

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
        $file = PathTestHelper::createPath(__FILE__);
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a symlink at %s', $file->absolute());
        self::assertTranslatableException(static function () use ($sut, $file): void {
            $sut->readLink($file);
        }, IOException::class, $message);
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonExistentFile(): void
    {
        $path = PathTestHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a symlink at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->readLink($path);
        }, IOException::class, $message);
    }

    /** @covers ::touch */
    public function testTouch(): void
    {
        $path = VfsTestHelper::arbitraryFilePath();
        $sut = $this->createSut();

        $sut->touch($path);

        self::assertFileExists($path->absolute());
    }

    /** @covers ::touch */
    public function testTouchAlreadyADirectory(): void
    {
        $directoryPath = VfsTestHelper::arbitraryDirPath();
        $directoryAbsolute = $directoryPath->absolute();
        FilesystemTestHelper::createDirectories([$directoryAbsolute]);
        $sut = $this->createSut();

        $message = sprintf('Cannot touch file--a directory already exists at %s', $directoryAbsolute);
        self::assertTranslatableException(static function () use ($sut, $directoryPath): void {
            $sut->touch($directoryPath);
        }, LogicException::class, $message);
    }
}
