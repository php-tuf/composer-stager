<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
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

    public static function createDirectories(array|string $directories, ?string $basePath = null): void
    {
        // Convert $directories to an array if only a single string is given.
        if (is_string($directories)) {
            $directories = [$directories];
        }

        // If a base path is provided, use it to make all directories absolute.
        if (is_string($basePath)) {
            $directories = array_map(static fn ($dirname): string => PathTestHelper::makeAbsolute($dirname, $basePath), $directories);
        }

        (new SymfonyFilesystem())->mkdir($directories);
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

    public static function remove(string|iterable $paths): void
    {
        self::symfonyFilesystem()->remove($paths);
    }

    public static function touch(string $path): void
    {
        self::ensureParentDirectory($path);
        self::symfonyFilesystem()->touch($path);

        assert(self::symfonyFilesystem()->exists($path), sprintf('Failed to touch file: %s', $path));
    }

    public static function ensureParentDirectory(string $filename): void
    {
        $dirname = dirname($filename);
        self::createDirectories($dirname);
    }

    private static function symfonyFilesystem(): SymfonyFilesystem
    {
        return new SymfonyFilesystem();
    }

    public static function createSymlinks(string $basePath, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createSymlink($basePath, $link, $target);
        }
    }

    public static function createSymlink(string $basePath, string $link, string $target): void
    {
        $link = PathTestHelper::createPath($link, $basePath);
        $target = PathTestHelper::createPath($target, $basePath);

        self::prepareForLink($link, $target);

        symlink($target->absolute(), $link->absolute());
    }

    public static function createHardlinks(string $basePath, array $symlinks): void
    {
        foreach ($symlinks as $link => $target) {
            self::createHardlink($basePath, $link, $target);
        }
    }

    public static function createHardlink(string $basePath, string $link, string $target): void
    {
        $link = PathTestHelper::createPath($link, $basePath);
        $target = PathTestHelper::createPath($target, $basePath);

        self::prepareForLink($link, $target);

        link($target->absolute(), $link->absolute());
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        self::ensureParentDirectory($link->absolute());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->absolute()), sprintf('Symlink target does not exist: %s', $target->absolute()));
    }
}
