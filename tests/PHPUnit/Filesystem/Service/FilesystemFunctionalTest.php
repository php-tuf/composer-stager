<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Filesystem\Service;

use PhpTuf\ComposerStager\API\Exception\IOException;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem;
use PhpTuf\ComposerStager\Internal\Host\Service\Host;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/** @coversDefaultClass \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem */
final class FilesystemFunctionalTest extends TestCase
{
    private static function sourceDir(): PathInterface
    {
        return PathFactory::create(self::TEST_ENV_ABSOLUTE . '/source');
    }

    private static function destinationDir(): PathInterface
    {
        return PathFactory::create(self::TEST_ENV_ABSOLUTE . '/destination');
    }

    protected function setUp(): void
    {
        FilesystemHelper::createDirectories([
            self::sourceDir()->absolute(),
            self::destinationDir()->absolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem $filesystem */
        $filesystem = $container->get(Filesystem::class);

        return $filesystem;
    }

    /** @covers ::copy */
    public function testCopy(): void
    {
        $filename = 'file.txt';
        $source = PathFactory::create($filename, self::sourceDir());
        $destination = PathFactory::create($filename, self::destinationDir());
        touch($source->absolute());

        $filesystem = $this->createSut();

        // Copy an individual file.
        $filesystem->copy($source, $destination);

        self::assertDirectoryListing(self::destinationDir()->absolute(), [$filename]);
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyTrue(): void
    {
        $directory = PathFactory::create(self::TEST_ENV_ABSOLUTE . '/empty');
        FilesystemHelper::createDirectories($directory->absolute());
        $sut = $this->createSut();

        self::assertTrue($sut->isDirEmpty($directory), 'Correctly detected empty directory.');
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyFalse(): void
    {
        $directory = PathFactory::create(__DIR__);
        $sut = $this->createSut();

        self::assertFalse($sut->isDirEmpty($directory), 'Correctly detected non-empty directory.');
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyErrorIsNotADirectory(): void
    {
        $path = PathFactory::create(self::TEST_ENV_ABSOLUTE);
        $file = PathFactory::create('file.txt', $path);
        touch($file->absolute());
        $message = sprintf(
            'The path does not exist or is not a directory at %s',
            $file->absolute(),
        );
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $file): void {
            $sut->isDirEmpty($file);
        }, IOException::class, $message);
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyError(): void
    {
        $path = PathFactory::create('non-existent');
        $message = sprintf(
            'The path does not exist or is not a directory at %s',
            $path->absolute(),
        );
        $sut = $this->createSut();

        self::assertTranslatableException(static function () use ($sut, $path): void {
            $sut->isDirEmpty($path);
        }, IOException::class, $message);
    }

    /**
     * @covers ::exists
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
        self::createFiles(self::sourceDir()->absolute(), $files);
        FilesystemHelper::createDirectories($directories, self::sourceDir()->absolute());
        self::createSymlinks(self::sourceDir()->absolute(), $symlinks);
        self::createHardlinks(self::sourceDir()->absolute(), $hardLinks);
        $subject = PathFactory::create($subject, self::sourceDir());
        $sut = $this->createSut();

        $actualExists = $sut->exists($subject);
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
                'subject' => 'non_existent_path.txt',
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
    public function testReadlink(string $given, string $expectedRaw, string $expectedAbsolute): void
    {
        $basePath = self::sourceDir();
        $symlinkPath = PathFactory::create('symlink.txt', $basePath);
        $hardLinkPath = PathFactory::create('hard_link.txt', $basePath);
        $targetPath = PathFactory::create($given, $basePath);
        chdir($basePath->absolute());
        touch($targetPath->absolute());
        symlink($given, $symlinkPath->absolute());
        link($given, $hardLinkPath->absolute());
        $sut = $this->createSut();

        // Change directory to make sure the result isn't affected by the CWD at runtime.
        chdir(__DIR__);

        $symlinkTarget = $sut->readLink($symlinkPath);

        self::assertEquals($expectedRaw, $symlinkTarget->raw(), 'Got the correct raw target value.');
        self::assertEquals($expectedAbsolute, $symlinkTarget->absolute(), 'Got the correct absolute target value.');

        $message = sprintf('The path does not exist or is not a symlink at %s', $hardLinkPath->absolute());
        self::assertTranslatableException(static function () use ($sut, $hardLinkPath): void {
            $sut->readLink($hardLinkPath);
        }, IOException::class, $message);
    }

    public function providerReadlink(): array
    {
        $absolute = static function ($path): string {
            $basePath = self::sourceDir();

            return PathFactory::create($path, $basePath)->absolute();
        };

        // Note: relative links cannot be distinguished from absolute links on Windows,
        // where readlink() canonicalizes the target path, making them appear identical.
        // Hence the below expected values conditioned on host.
        return [
            'Absolute link' => [
                'given' => $absolute('target.txt'),
                'expectedRaw' => $absolute('target.txt'),
                'expectedAbsolute' => $absolute('target.txt'),
            ],
            'Relative link' => [
                'given' => 'target.txt',
                'expectedRaw' => Host::isWindows() ? $absolute('target.txt') : 'target.txt',
                'expectedAbsolute' => $absolute('target.txt'),
            ],
        ];
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonLink(): void
    {
        $file = PathFactory::create('file.txt', self::sourceDir());
        touch($file->absolute());
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
        $path = PathFactory::create('non-existent_file.txt', self::sourceDir());
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
            preg_quote(self::sourceDir()->absolute(), '/'),
        ));

        $filename = 'arbitrary_file.txt';
        $sourceFile = PathFactory::create($filename, self::sourceDir());
        $destinationFile = PathFactory::create($filename, self::destinationDir());
        touch($sourceFile->absolute());
        assert(file_exists($sourceFile->absolute()));

        $sut = new SymfonyFilesystem();

        // Single file copy: this should work.
        $sut->copy(
            $sourceFile->absolute(),
            $destinationFile->absolute(),
        );

        // Directory copy: this should fail.
        $sut->copy(
            self::sourceDir()->absolute(),
            self::destinationDir()->absolute(),
        );
    }
}
