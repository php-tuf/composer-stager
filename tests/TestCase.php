<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;
use Symfony\Component\Process\Process;

abstract class TestCase extends PHPUnitTestCase
{
    use ProphecyTrait;

    protected const PROJECT_ROOT = __DIR__ . '/..';
    protected const TEST_ENV = self::PROJECT_ROOT . '/var/phpunit/test-env';
    protected const TEST_WORKING_DIR = self::TEST_ENV . '/working-dir';
    protected const ACTIVE_DIR = 'active-dir';
    protected const STAGING_DIR = 'staging-dir';
    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    public function getContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(self::PROJECT_ROOT . '/config/services.yml');

        return $container;
    }

    protected static function createTestEnvironment(string $activeDir): void
    {
        self::removeTestEnvironment();

        $filesystem = new Filesystem();

        // Create the test environment.
        $filesystem->mkdir(self::TEST_WORKING_DIR);
        chdir(self::TEST_WORKING_DIR);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir($activeDir);
    }

    protected static function removeTestEnvironment(): void
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists(self::TEST_ENV)) {
            return;
        }

        try {
            $filesystem->remove(self::TEST_ENV);
        } catch (IOException) {
            // @todo Windows chokes on this every time, e.g.,
            //    | Failed to remove directory
            //    | "D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container":
            //    | rmdir(D:\a\composer-stager\composer-stager\tests\Functional/../../var/phpunit/test-env-container):
            //    | Resource temporarily unavailable.
            //   Obviously, this error suppression is likely to bite us in the future
            //   even though it doesn't seem to cause any problems now. Fix it.
            // @ignoreException
        }
    }

    protected static function createFiles(string $baseDir, array $filenames): void
    {
        foreach ($filenames as $filename) {
            self::createFile($baseDir, $filename);
        }
    }

    protected static function createFile(string $baseDir, string $filename): void
    {
        $filename = PathFactory::create("{$baseDir}/{$filename}")->resolved();
        static::ensureParentDirectory($filename);

        $touchResult = touch($filename);
        $realpathResult = realpath($filename);

        assert($touchResult, "Created file {$filename}.");
        assert($realpathResult !== false, "Got absolute path of {$filename}.");
    }

    protected static function createDirectories(string $baseDir, array $dirnames): void
    {
        foreach ($dirnames as $dirname) {
            self::createDirectory($baseDir, $dirname);
        }
    }

    protected static function createDirectory(string $baseDir, string $dirname): void
    {
        $dirname = PathFactory::create("{$baseDir}/{$dirname}")->resolved();
        static::ensureParentDirectory($dirname);

        $touchResult = mkdir($dirname);
        $realpathResult = realpath($dirname);

        assert($touchResult, "Created directory {$dirname}.");
        assert($realpathResult !== false, "Got absolute path of {$dirname}.");
    }

    protected static function createSymlinks(string $baseDir, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createSymlink($baseDir, $link, $target);
        }
    }

    protected static function createSymlink(string $baseDir, string $link, string $target): void
    {
        $link = PathFactory::create("{$baseDir}/{$link}");
        $target = PathFactory::create("{$baseDir}/{$target}");

        self::prepareForLink($link, $target);

        symlink($target->resolved(), $link->resolved());
    }

    protected static function createHardlinks(string $baseDir, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createHardlink($baseDir, $link, $target);
        }
    }

    protected static function createHardlink(string $baseDir, string $link, string $target): void
    {
        $link = PathFactory::create("{$baseDir}/{$link}");
        $target = PathFactory::create("{$baseDir}/{$target}");

        self::prepareForLink($link, $target);

        link($target->resolved(), $link->resolved());
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        static::ensureParentDirectory($link->resolved());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->resolved()), 'Symlink target exists.');
    }

    protected static function ensureParentDirectory(string $filename): void
    {
        $dirname = dirname($filename);

        if (file_exists($dirname)) {
            return;
        }

        $mkdirResult = mkdir($dirname, 0777, true);
        assert($mkdirResult, "Created directory {$dirname}.");
    }

    protected static function changeFile($dir, $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($pathname, self::CHANGED_CONTENT);
        assert($result !== false, "Changed file {$pathname}.");
    }

    protected static function deleteFile($dir, $filename): void
    {
        $pathname = self::ensureTrailingSlash($dir) . $filename;
        $result = unlink($pathname);
        assert($result, "Deleted file {$pathname}.");
    }

    protected static function fixSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /** phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference */
    protected static function fixSeparatorsMultiple(&...$paths): void
    {
        foreach ($paths as &$path) {
            $path = self::fixSeparators($path);
        }
    }

    /**
     * Asserts a flattened directory listing similar to what GNU find would
     * return, alphabetized for easier comparison. Example:
     * ```php
     * [
     *     'eight.txt',
     *     'four/five.txt',
     *     'one/two/three.txt',
     *     'six/seven.txt',
     * ];
     * ```
     */
    protected static function assertDirectoryListing(
        string $dir,
        array $expected,
        string $ignoreDir = '',
        string $message = '',
    ): void {
        $expected = array_map([self::class, 'fixSeparators'], $expected);

        $actual = self::getFlatDirectoryListing($dir);

        // Remove ignored paths.
        $actual = array_map(static function ($path) use ($dir, $ignoreDir) {
            // Paths must be prefixed with the given directory for "ignored paths"
            // matching but returned un-prefixed for later expectation comparison.
            $matchPath = self::ensureTrailingSlash($dir) . $path;
            $ignoreDir = self::ensureTrailingSlash($ignoreDir);

            if (str_starts_with($matchPath, $ignoreDir)) {
                return false;
            }

            return self::fixSeparators($path);
        }, $actual);

        // Normalize arrays for comparison.
        $expected = array_filter($expected);
        asort($expected);
        $actual = array_filter($actual);
        asort($actual);

        // Make diffs easier to read by eliminating noise coming from numeric keys.
        $expected = array_fill_keys($expected, 0);
        $actual = array_fill_keys($actual, 0);

        if ($message === '') {
            $message = "Directory {$dir} contains the expected files.";
        }

        self::assertEquals($expected, $actual, $message);
    }

    protected static function getDirectoryContents(string $dir): array
    {
        $dir = self::ensureTrailingSlash($dir);
        $dirListing = self::getFlatDirectoryListing($dir);

        $contents = [];

        foreach ($dirListing as $pathname) {
            if (is_link($dir . $pathname)) {
                $contents[$pathname] = '';

                continue;
            }

            $contents[$pathname] = file_get_contents($dir . $pathname);
        }

        return $contents;
    }

    /**
     * Returns a flattened directory listing similar to what GNU find would,
     * alphabetized for easier comparison. Example:
     * ```php
     * [
     *     'eight.txt',
     *     'four/five.txt',
     *     'one/two/three.txt',
     *     'six/seven.txt',
     * ];
     * ```
     */
    protected static function getFlatDirectoryListing(string $dir): array
    {
        $dir = self::stripTrailingSlash($dir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        $listing = [];

        foreach ($iterator as $splFileInfo) {
            assert($splFileInfo instanceof SplFileInfo);

            if (in_array($splFileInfo->getFilename(), ['.', '..'], true)) {
                continue;
            }

            $pathname = $splFileInfo->getPathname();
            $listing[] = substr($pathname, strlen($dir) + 1);
        }

        sort($listing);

        return array_values($listing);
    }

    /**
     * Strips the trailing slash (directory separator) from a given path.
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not. Empty paths and device
     *   roots will be returned unchanged. Remote paths and UNC (Universal
     *   Naming Convention) paths are not supported. No validation is done to
     *   ensure that given paths are valid.
     */
    protected static function stripTrailingSlash(string $path): string
    {
        // Don't change a Windows drive letter root path, e.g., "C:\".
        if (preg_match('/^[a-z]:\\\\?$/i', $path) === 1) {
            return $path;
        }

        $trimmedPath = rtrim($path, '/\\');

        // Don't change a UNIX-like root path.
        if ($trimmedPath === '') {
            return $path;
        }

        return $trimmedPath;
    }

    /**
     * Ensures that the given path ends with a slash (directory separator).
     *
     * @param string $path
     *   Any path, absolute or relative, existing or not.
     */
    protected static function ensureTrailingSlash(string $path): string
    {
        if ($path === '') {
            $path = '.';
        }

        return self::stripTrailingSlash($path) . DIRECTORY_SEPARATOR;
    }

    protected static function assertFileChanged($dir, $path, $message = ''): void
    {
        self::assertStringEqualsFile(
            self::ensureTrailingSlash($dir) . $path,
            self::CHANGED_CONTENT,
            $message,
        );
    }

    protected static function assertFileNotChanged($dir, $path, $message = ''): void
    {
        self::assertStringEqualsFile(
            self::ensureTrailingSlash($dir) . $path,
            self::ORIGINAL_CONTENT,
            $message,
        );
    }

    protected static function assertStagingDirectoryDoesNotExist(): void
    {
        self::assertFileDoesNotExist(self::STAGING_DIR, 'Staging directory does not exist.');
    }

    protected static function assertActiveAndStagingDirectoriesSame(): void
    {
        self::assertSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are the same.',
        );
    }

    protected static function assertActiveAndStagingDirectoriesNotSame(): void
    {
        self::assertNotSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are not the same.',
        );
    }

    protected static function getActiveAndStagingDirectoriesDiff(): string
    {
        $process = new Process([
            'diff',
            '--recursive',
            self::ACTIVE_DIR,
            self::STAGING_DIR,
        ]);
        $process->run();

        return $process->getOutput();
    }

    protected static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR !== '/';
    }

    protected static function isRsyncAvailable(): bool
    {
        $finder = new SymfonyExecutableFinder();

        return $finder->find('rsync') !== null;
    }
}
