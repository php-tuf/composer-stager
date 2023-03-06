<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Domain\Exception\IOException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \Symfony\Component\Filesystem\Filesystem
 */
final class FilesystemFunctionalTest extends TestCase
{
    private const SOURCE_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'source';
    private const DESTINATION_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'destination';

    protected function setUp(): void
    {
        $filesystem = new SymfonyFilesystem();

        $filesystem->mkdir(self::SOURCE_DIR);
        $filesystem->mkdir(self::DESTINATION_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): Filesystem
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
        self::createFile(self::SOURCE_DIR, $filename);

        $filesystem = $this->createSut();

        $source = PathFactory::create(self::SOURCE_DIR . DIRECTORY_SEPARATOR . $filename);
        $destination = PathFactory::create(self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $filename);

        // Copy an individual file.
        $filesystem->copy($source, $destination);

        self::assertDirectoryListing(self::DESTINATION_DIR, [$filename]);
    }

    /**
     * @covers ::exists
     * @covers ::getFileType
     * @covers ::isHardLink
     * @covers ::isLink
     * @covers ::isSymlink
     *
     * @dataProvider providerExistsAndLinkChecks
     */
    public function testExistsAndLinkChecks(
        array $files,
        array $directories,
        array $symlinks,
        array $hardLinks,
        string $subject,
        bool $exists,
        bool $isLink,
        bool $isHardLink,
        bool $isSymlink,
    ): void {
        self::createFiles(self::SOURCE_DIR, $files);
        self::createDirectories(self::SOURCE_DIR, $directories);
        self::createSymlinks(self::SOURCE_DIR, $symlinks);
        self::createHardlinks(self::SOURCE_DIR, $hardLinks);
        $subject = PathFactory::create(self::SOURCE_DIR . '/' . $subject);
        $sut = $this->createSut();

        $actualExists = $sut->exists($subject);
        $actualIsLink = $sut->isLink($subject);
        $actualIsHardLink = $sut->isHardLink($subject);
        $actualIsSymlink = $sut->isSymlink($subject);

        self::assertSame($exists, $actualExists, 'Correctly determined whether path exists.');
        self::assertSame($isLink, $actualIsLink, 'Correctly determined whether path is a link.');
        self::assertSame($isHardLink, $actualIsHardLink, 'Correctly determined whether path is a hard link.');
        self::assertSame($isSymlink, $actualIsSymlink, 'Correctly determined whether path is a symlink.');
    }

    public function providerExistsAndLinkChecks(): array
    {
        return [
            'Path is a symlink to a file' => [
                'files' => ['target.txt'],
                'directories' => [],
                'symlinks' => ['symlink.txt' => 'target.txt'],
                'hardLinks' => [],
                'subject' => 'symlink.txt',
                'exists' => true,
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
                'isLink' => true,
                'isHardLink' => false,
                'isSymlink' => true,
            ],
            // Creating a hard link to a directory is not a permitted
            // operation. Just test with a file.
            'Path is a hard link' => [
                'files' => ['target.txt'],
                'directories' => [],
                'symlinks' => [],
                'hardLinks' => ['hard_link.txt' => 'target.txt'],
                'subject' => 'hard_link.txt',
                'exists' => true,
                'isLink' => true,
                'isHardLink' => true,
                'isSymlink' => false,
            ],
            'Path is a file' => [
                'files' => ['file.txt'],
                'directories' => [],
                'symlinks' => [],
                'hardLinks' => [],
                'subject' => 'file.txt',
                'exists' => true,
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
    public function testReadlink(string $given, string $expected): void
    {
        $baseDir = PathFactory::create(self::SOURCE_DIR);
        $symlinkPath = PathFactory::create('symlink.txt', $baseDir);
        $hardLinkPath = PathFactory::create('hard_link.txt', $baseDir);
        $targetPath = PathFactory::create($given, $baseDir);
        chdir($baseDir->resolve());
        touch($targetPath->resolve());
        symlink($given, $symlinkPath->resolve());
        link($given, $hardLinkPath->resolve());
        $sut = $this->createSut();

        $symlinkTarget = $sut->readLink($symlinkPath);

        self::assertEquals($expected, $symlinkTarget->raw(), 'Got the correct symlink target.');

        $this->expectException(IOException::class);
        $message = sprintf('The path does not exist or is not a symlink at "%s"', $hardLinkPath->resolve());
        $this->expectExceptionMessage($message);
        $sut->readLink($hardLinkPath);
    }

    public function providerReadlink(): array
    {
        $fileName = 'target.txt';
        $absolutePath = PathFactory::create($fileName, PathFactory::create(self::SOURCE_DIR))->resolve();

        $data = [
            'Absolute link' => [
                'given' => $absolutePath,
                'expected' => $absolutePath,
            ],
        ];

        // Relative links cannot be distinguished from absolute links on Windows,
        // where readlink() canonicalizes the target path, making them appear identical.
        // At least test that the behavior is as expected in either case.
        $data['Relative link'] = self::isWindows() ? [
            'given' => $fileName,
            'expected' => $absolutePath,
        ] : [
            'given' => $fileName,
            'expected' => $fileName,
        ];

        return $data;
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonLink(): void
    {
        self::createFile(self::SOURCE_DIR, 'file.txt');
        $file = PathFactory::create(self::SOURCE_DIR . '/file.txt');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('The path does not exist or is not a symlink at "%s"', $file->resolve()));

        $sut = $this->createSut();

        $sut->readLink($file);
    }

    /** @covers ::readLink */
    public function testReadlinkOnNonExistentFile(): void
    {
        $path = self::SOURCE_DIR . DIRECTORY_SEPARATOR . 'non-existent_file.txt';
        $path = PathFactory::create($path);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage(sprintf('The path does not exist or is not a symlink at "%s"', $path->resolve()));

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

        $dirname = 'directory';
        $files = [
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file2',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file3',
        ];
        self::createFiles(self::SOURCE_DIR, $files);
        $symfonyFilesystem = new SymfonyFilesystem();

        self::assertDirectoryListing(self::SOURCE_DIR, $files);

        // Single file copy: this should work.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
        );

        // Directory copy: this should fail.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname,
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname,
        );
    }
}
