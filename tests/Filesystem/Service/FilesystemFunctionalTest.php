<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem */
final class FilesystemFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
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

    /** @covers ::copy */
    public function testCopy(): void
    {
        $mode = 0775;
        $filenameRelative = 'file.txt';
        $sourceFilePath = PathHelper::createPath($filenameRelative, PathHelper::sourceDirAbsolute());
        $sourceFileAbsolute = $sourceFilePath->absolute();
        $destinationFilePath = PathHelper::createPath($filenameRelative, PathHelper::destinationDirAbsolute());
        $destinationFileAbsolute = $destinationFilePath->absolute();
        FilesystemHelper::touch($sourceFileAbsolute);
        assert(FilesystemHelper::fileMode($sourceFileAbsolute) !== $mode, "The new file doesn't already have the same permissions as will be set.");
        FilesystemHelper::chmod($sourceFileAbsolute, $mode);
        $sut = $this->createSut();

        // Copy an individual file.
        $sut->copy($sourceFilePath, $destinationFilePath);

        self::assertFileMode($destinationFileAbsolute, $mode);
        self::assertDirectoryListing(PathHelper::destinationDirAbsolute(), [$filenameRelative]);
    }

    /** @covers ::copy */
    public function testCopySourceFileNotFound(): void
    {
        $filenameRelative = 'file.txt';
        $sourceFilePath = PathHelper::createPath($filenameRelative, PathHelper::sourceDirAbsolute());
        $sourceFileAbsolute = $sourceFilePath->absolute();
        $destinationFilePath = PathHelper::createPath($filenameRelative, PathHelper::destinationDirAbsolute());
        $sut = $this->createSut();

        $message = sprintf('No such file: %s', $sourceFileAbsolute);
        self::assertTranslatableException(static function () use ($sut, $sourceFilePath, $destinationFilePath): void {
            $sut->copy($sourceFilePath, $destinationFilePath);
        }, LogicException::class, $message);
    }

    /**
     * @covers ::chmod
     *
     * @group no_windows
     */
    public function testChmod(): void
    {
        $mode = 0775;
        $filePath = PathHelper::createPath('file.txt', PathHelper::sourceDirAbsolute());
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
        $path = PathHelper::nonExistentFilePath();
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
        $file = PathHelper::createPath('file.txt', PathHelper::sourceDirAbsolute());
        $fileAbsolute = $file->absolute();
        FilesystemHelper::touch($fileAbsolute);
        FilesystemHelper::chmod($fileAbsolute, $mode);
        $sut = $this->createSut();

        $actual = $sut->fileMode($file);

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
        $directoryPath = PathHelper::createPath('directory', PathHelper::sourceDirAbsolute());
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
        $path = PathHelper::nonExistentFilePath();
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
        $filePath = PathHelper::createPath('test.txt', PathHelper::sourceDirAbsolute());
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

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyTrue(): void
    {
        $directoryPath = PathHelper::createPath('empty', PathHelper::testPersistentFixturesAbsolute());
        FilesystemHelper::createDirectories($directoryPath->absolute());
        $sut = $this->createSut();

        self::assertTrue($sut->isDirEmpty($directoryPath), 'Correctly detected empty directory.');

        FilesystemHelper::remove(PathHelper::testPersistentFixturesAbsolute());
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyFalse(): void
    {
        $directoryPath = PathHelper::createPath(__DIR__);
        $sut = $this->createSut();

        self::assertFalse($sut->isDirEmpty($directoryPath), 'Correctly detected non-empty directory.');
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyErrorIsNotADirectory(): void
    {
        $filePath = PathHelper::createPath('file.txt', PathHelper::testFreshFixturesDirAbsolute());
        FilesystemHelper::touch($filePath->absolute());
        $message = sprintf(
            'The path does not exist or is not a directory at %s',
            $filePath->absolute(),
        );
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $filePath): void {
            $sut->isDirEmpty($filePath);
        }, IOException::class, $message);
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyError(): void
    {
        $path = PathHelper::nonExistentFilePath();
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a directory at %s', $path->absolute());
        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->isDirEmpty($path);
        }, IOException::class, $message);
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
        $file = PathHelper::createPath('file.txt', PathHelper::sourceDirAbsolute());
        FilesystemHelper::touch($file->absolute());
        assert(file_exists($file->absolute()));
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

    /**
     * Our filesystem service currently depends on the Symfony Filesystem component
     * and currently delegates its copy() method directly to it. Therefore, it is
     * precisely equivalent to its implementation. Symfony's copy() documentation
     * does not specify whether it supports directories as well as files. This
     * test is to discover whether it does. (At this time it does not.)
     *
     * @coversNothing
     */
    public function testSymfonyCopyDirectory(): void
    {
        $this->expectException(SymfonyIOException::class);
        $this->expectExceptionMessageMatches(sprintf(
            '#"%s"#',
            preg_quote(PathHelper::sourceDirAbsolute(), '/'),
        ));

        $filename = 'arbitrary_file.txt';
        $sourceFile = PathHelper::createPath($filename, PathHelper::sourceDirAbsolute());
        $destinationFile = PathHelper::createPath($filename, PathHelper::destinationDirAbsolute());
        FilesystemHelper::touch($sourceFile->absolute());

        $sut = new SymfonyFilesystem();

        // Single file copy: this should work.
        $sut->copy(
            $sourceFile->absolute(),
            $destinationFile->absolute(),
        );

        // Directory copy: this should fail.
        $sut->copy(
            PathHelper::sourceDirAbsolute(),
            PathHelper::destinationDirPath()->absolute(),
        );
    }
}
