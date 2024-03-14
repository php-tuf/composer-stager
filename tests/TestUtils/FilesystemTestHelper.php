<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class FilesystemTestHelper
{
    public static function chmod(string $path, int $mode): void
    {
        assert(self::exists($path), sprintf('Path does not exist: %s', $path));

        chmod($path, $mode);

        clearstatcache(true, $path);

        if (EnvironmentTestHelper::isWindows()) {
            return;
        }

        assert(self::fileMode($path) === $mode, sprintf('Failed to set file mode: %s', $path));
    }

    /**
     * @param array<string, string> $hardlinks
     *   An array of hard links values, keyed by the link (source) path
     *   with a corresponding value of the link target path.
     */
    public static function createHardlinks(array $hardlinks, ?string $basePath = null): void
    {
        self::doCreateLinks($hardlinks, $basePath, 'link');
    }

    /**
     * @param array<string, string> $symlinks
     *   An array of symlink values, keyed by the link (source) path
     *   with a corresponding value of the link target path.
     */
    public static function createSymlinks(array $symlinks, ?string $basePath = null): void
    {
        self::doCreateLinks($symlinks, $basePath, 'symlink');
    }

    public static function ensureParentDirectory(string|array $filenames): void
    {
        $filenames = BasicTestHelper::ensureIsArray($filenames);

        foreach ($filenames as $filename) {
            $dirname = dirname((string) $filename);
            self::mkdir($dirname);
        }
    }

    public static function exists(string $path): bool
    {
        return self::symfonyFilesystem()->exists($path);
    }

    public static function fileMode(string $path): int
    {
        assert(file_exists($path), sprintf('Path does not exist: %s', $path));

        clearstatcache(true, $path);

        $mode = fileperms($path) & 0777;

        clearstatcache(true, $path);

        return $mode;
    }

    public static function getDirectoryContents(string $dir): array
    {
        $dir = PathTestHelper::ensureTrailingSlash($dir);
        $dirListing = self::getFlatDirectoryListing($dir);

        $contents = [];

        foreach ($dirListing as $pathAbsolute) {
            if (is_link($dir . $pathAbsolute)) {
                $contents[$pathAbsolute] = '';

                continue;
            }

            $contents[$pathAbsolute] = file_get_contents($dir . $pathAbsolute);
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
    public static function getFlatDirectoryListing(string $dir): array
    {
        $dir = PathTestHelper::stripTrailingSlash($dir);

        if (!self::exists($dir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
        );

        $listing = [];

        foreach ($iterator as $splFileInfo) {
            assert($splFileInfo instanceof SplFileInfo);

            if (in_array($splFileInfo->getFilename(), ['.', '..'], true)) {
                continue;
            }

            $pathAbsolute = $splFileInfo->getPathname();
            $listing[] = substr($pathAbsolute, strlen($dir) + 1);
        }

        sort($listing);

        return array_values($listing);
    }

    public static function mkdir(array|string $directories, ?string $basePath = null): void
    {
        $directories = self::makeAbsolute($directories, $basePath);
        (new SymfonyFilesystem())->mkdir($directories);
    }

    public static function rm(string|iterable $paths): void
    {
        self::symfonyFilesystem()->remove($paths);
    }

    public static function touch(string|array $paths, ?string $basePath = null): void
    {
        $paths = self::makeAbsolute($paths, $basePath);
        self::ensureParentDirectory($paths);
        self::symfonyFilesystem()->touch($paths);
    }

    private static function doCreateLinks(array $links, ?string $basePath, string $linkFunction): void
    {
        $basePath ??= FixtureTestHelper::testFreshFixturesDirAbsolute();
        $previousCwd = getcwd();
        chdir($basePath);

        foreach ($links as $link => $target) {
            $linkPath = PathTestHelper::createPath($link, $basePath);
            $targetPath = PathTestHelper::createPath($target, $basePath);

            self::prepareForLink($linkPath, $targetPath);

            $linkFunction($target, $linkPath->absolute());
        }

        chdir($previousCwd);
    }

    /** @todo Replace with \PhpTuf\ComposerStager\Tests\TestUtils\PathTestHelper::makeAbsolute(), if possible */
    private static function makeAbsolute(array|string $paths, ?string $basePath): string|array
    {
        if (is_string($basePath)) {
            return PathTestHelper::makeAbsolute($paths, $basePath);
        }

        return $paths;
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        self::ensureParentDirectory($link->absolute());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->absolute()), sprintf('Symlink target does not exist: %s', $target->absolute()));
    }

    private static function symfonyFilesystem(): SymfonyFilesystem
    {
        return new SymfonyFilesystem();
    }
}
