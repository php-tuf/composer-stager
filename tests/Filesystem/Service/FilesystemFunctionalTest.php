<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\VfsTestHelper;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem */
final class FilesystemFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        VfsTestHelper::setup();
        self::mkdir([
            self::sourceDirAbsolute(),
            self::destinationDirAbsolute(),
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
     * @covers ::chmod
     *
     * @group no_windows
     */
    public function testChmod(): void
    {
        $mode = 0775;
        $filePath = VfsTestHelper::arbitraryFilePath();
        $fileAbsolute = $filePath->absolute();
        self::touch($fileAbsolute);
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
        self::touch($files, self::sourceDirAbsolute());
        self::mkdir($directories, self::sourceDirAbsolute());
        self::createSymlinks($symlinks, self::sourceDirAbsolute());
        self::createHardlinks($hardLinks, self::sourceDirAbsolute());
        $subject = self::createPath($subject, self::sourceDirAbsolute());
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
                'subject' => self::nonExistentFileRelative(),
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
        $basePath = self::sourceDirAbsolute();
        $symlinkPath = self::createPath('symlink.txt', $basePath);
        $hardLinkPath = self::createPath('hard_link.txt', $basePath);
        $targetPath = self::createPath($given, $basePath);
        chdir($basePath);
        self::touch($targetPath->absolute());
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
        $basePath = self::sourceDirAbsolute();
        $absolute = static fn ($path): string => self::makeAbsolute($path, $basePath);

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
        $file = self::createPath(__FILE__);
        $sut = $this->createSut();

        $message = sprintf('The path does not exist or is not a symlink at %s', $file->absolute());
        self::assertTranslatableException(static function () use ($sut, $file): void {
            $sut->readLink($file);
        }, IOException::class, $message);
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonExistentFile(): void
    {
        $path = self::nonExistentFilePath();
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
        self::mkdir([$directoryAbsolute]);
        $sut = $this->createSut();

        $message = sprintf('Cannot touch file--a directory already exists at %s', $directoryAbsolute);
        self::assertTranslatableException(static function () use ($sut, $directoryPath): void {
            $sut->touch($directoryPath);
        }, LogicException::class, $message);
    }
}
