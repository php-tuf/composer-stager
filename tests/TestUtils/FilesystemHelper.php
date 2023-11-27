<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

final class FilesystemHelper
{
    public static function chmod(string $path, int $permissions): void
    {
        assert(PathHelper::isAbsolute($path));
        assert(self::exists($path));

        chmod($path, $permissions);

        assert(self::filePerms($path) === (int) decoct($permissions), 'chmod worked.');

        clearstatcache(true, $path);
    }

    public static function createDirectories(array|string $directories, ?string $basePath = null): void
    {
        // Convert $directories to an array if only a single string is given.
        if (is_string($directories)) {
            $directories = [$directories];
        }

        // If a base path is provided, use it to make all directories absolute.
        if (is_string($basePath)) {
            assert(PathHelper::isAbsolute($basePath), 'Base path must be absolute.');
            $directories = array_map(static fn ($dirname): string => PathHelper::makeAbsolute($dirname, $basePath), $directories);
        }

        (new SymfonyFilesystem())->mkdir($directories);
    }

    public static function exists(string $path): bool
    {
        assert(PathHelper::isAbsolute($path));

        return self::symfonyFilesystem()->exists($path);
    }

    public static function filePerms(string $path): int
    {
        assert(PathHelper::isAbsolute($path), 'The given path is absolute.');
        assert(file_exists($path), 'The path exists');

        clearstatcache(true, $path);

        // Try to do everything a little differently than the corresponding
        // production code so as to avoid comparing assumptions to assumptions
        // instead of actually testing code. Convert the permissions to a
        // decimal value in two ways and compare them as a sanity check.
        $permissions = fileperms($path);
        assert($permissions === stat($path)['mode']);

        $firstWay = substr(sprintf('%o', $permissions), -4);
        $firstWay = octdec($firstWay);

        $secondWay = decoct($permissions);
        $secondWay -= 100_000;
        $secondWay = octdec(sprintf('%s', $secondWay));

        assert($firstWay === $secondWay, 'Both ways return the same result.');

        clearstatcache(true, $path);

        return $firstWay;
    }

    public static function remove(string|iterable $paths): void
    {
        self::symfonyFilesystem()->remove($paths);
    }

    public static function touch(string $path): void
    {
        assert(PathHelper::isAbsolute($path));

        self::ensureParentDirectory($path);
        self::symfonyFilesystem()->touch($path);

        assert(self::symfonyFilesystem()->exists($path));
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
        $link = PathHelper::createPath($link, $basePath);
        $target = PathHelper::createPath($target, $basePath);

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
        $link = PathHelper::createPath($link, $basePath);
        $target = PathHelper::createPath($target, $basePath);

        self::prepareForLink($link, $target);

        link($target->absolute(), $link->absolute());
    }

    private static function prepareForLink(PathInterface $link, PathInterface $target): void
    {
        self::ensureParentDirectory($link->absolute());

        // If the symlink target doesn't exist, the tests will pass on Unix-like
        // systems but fail on Windows. Avoid hard-to-debug problems by making
        // sure it fails everywhere in that case.
        assert(file_exists($target->absolute()), 'Symlink target exists.');
    }
}
