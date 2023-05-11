<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Infrastructure\Service\Host\Host;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \Symfony\Component\Filesystem\Filesystem
 */
final class FilesystemFunctionalTest extends TestCase
{
    private static function sourceDir(): PathInterface
    {
        return PathFactory::create(self::TEST_ENV . '/source');
    }

    private static function destinationDir(): PathInterface
    {
        return PathFactory::create(self::TEST_ENV . '/destination');
    }

    protected function setUp(): void
    {
        // Create source directory.
        mkdir(self::sourceDir()->resolved(), 0777, true);
        assert(file_exists(self::sourceDir()->resolved()));
        assert(is_dir(self::sourceDir()->resolved()));

        // Create destination directory.
        mkdir(self::destinationDir()->resolved(), 0777, true);
        assert(file_exists(self::destinationDir()->resolved()));
        assert(is_dir(self::destinationDir()->resolved()));
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): Filesystem
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem $filesystem */
        $filesystem = $container->get(Filesystem::class);

        return $filesystem;
    }

    /** @covers ::copy */
    public function testCopy(): void
    {
        $filename = 'file.txt';
        $source = PathFactory::create($filename, self::sourceDir());
        $destination = PathFactory::create($filename, self::destinationDir());
        touch($source->resolved());

        $filesystem = $this->createSut();

        // Copy an individual file.
        $filesystem->copy($source, $destination);

        self::assertDirectoryListing(self::destinationDir()->resolved(), [$filename]);
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyTrue(): void
    {
        $directory = PathFactory::create(self::TEST_ENV . '/empty');
        mkdir($directory->resolved());
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
        $path = PathFactory::create(self::TEST_ENV);
        $file = PathFactory::create('file.txt', $path);
        touch($file->resolved());

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf(
            'The path does not exist or is not a directory at "%s"',
            $file->resolved(),
        ));

        $sut = $this->createSut();

        $sut->isDirEmpty($file);
    }

    /** @covers ::isDirEmpty */
    public function testIsDirEmptyError(): void
    {
        $path = PathFactory::create('non-existent');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf(
            'The path does not exist or is not a directory at "%s"',
            $path->resolved(),
        ));

        $sut = $this->createSut();

        $sut->isDirEmpty($path);
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
        self::createFiles(self::sourceDir()->resolved(), $files);
        self::createDirectories(self::sourceDir()->resolved(), $directories);
        self::createSymlinks(self::sourceDir()->resolved(), $symlinks);
        self::createHardlinks(self::sourceDir()->resolved(), $hardLinks);
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
    public function testReadlink(string $given, string $expectedRaw, string $expectedResolved): void
    {
        $baseDir = self::sourceDir();
        $symlinkPath = PathFactory::create('symlink.txt', $baseDir);
        $hardLinkPath = PathFactory::create('hard_link.txt', $baseDir);
        $targetPath = PathFactory::create($given, $baseDir);
        chdir($baseDir->resolved());
        touch($targetPath->resolved());
        symlink($given, $symlinkPath->resolved());
        link($given, $hardLinkPath->resolved());
        $sut = $this->createSut();

        // Change directory to make sure the result isn't affected by the CWD at runtime.
        chdir(__DIR__);

        $symlinkTarget = $sut->readLink($symlinkPath);

        self::assertEquals($expectedRaw, $symlinkTarget->raw(), 'Got the correct raw target value.');
        self::assertEquals($expectedResolved, $symlinkTarget->resolved(), 'Got the correct resolved target value.');

        $this->expectException(IOException::class);
        $message = sprintf('The path does not exist or is not a symlink at "%s"', $hardLinkPath->resolved());
        $this->expectExceptionMessage($message);
        $sut->readLink($hardLinkPath);
    }

    public function providerReadlink(): array
    {
        $absolute = static function ($path): string {
            $baseDir = self::sourceDir();

            return PathFactory::create($path, $baseDir)->resolved();
        };

        // Note: relative links cannot be distinguished from absolute links on Windows,
        // where readlink() canonicalizes the target path, making them appear identical.
        // Hence the below expected values conditioned on host.
        return [
            'Absolute link' => [
                'given' => $absolute('target.txt'),
                'expectedRaw' => $absolute('target.txt'),
                'expectedResolved' => $absolute('target.txt'),
            ],
            'Relative link' => [
                'given' => 'target.txt',
                'expectedRaw' => Host::isWindows() ? $absolute('target.txt') : 'target.txt',
                'expectedResolved' => $absolute('target.txt'),
            ],
        ];
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonLink(): void
    {
        $file = PathFactory::create('file.txt', self::sourceDir());
        touch($file->resolved());
        assert(file_exists($file->resolved()));

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('The path does not exist or is not a symlink at "%s"', $file->resolved()));

        $sut = $this->createSut();

        $sut->readLink($file);
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonExistentFile(): void
    {
        $path = PathFactory::create('non-existent_file.txt', self::sourceDir());

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('The path does not exist or is not a symlink at "%s"', $path->resolved()));

        $sut = $this->createSut();

        $sut->readLink($path);
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
            preg_quote(self::sourceDir()->resolved(), '/'),
        ));

        $filename = 'arbitrary_file.txt';
        $sourceFile = PathFactory::create($filename, self::sourceDir());
        $destinationFile = PathFactory::create($filename, self::destinationDir());
        touch($sourceFile->resolved());
        assert(file_exists($sourceFile->resolved()));

        $sut = new SymfonyFilesystem();

        // Single file copy: this should work.
        $sut->copy(
            $sourceFile->resolved(),
            $destinationFile->resolved(),
        );

        // Directory copy: this should fail.
        $sut->copy(
            self::sourceDir()->resolved(),
            self::destinationDir()->resolved(),
        );
    }
}
