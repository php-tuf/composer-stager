<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper as Helper;

/**
 * Provides convenience methods for FilesystemTestHelper calls.
 *
 * @see \PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper
 */
trait FilesystemTestHelperTrait
{
    public static function chmod(string $path, int $mode): void
    {
        Helper::chmod($path, $mode);
    }

    public static function createHardlinks(string $basePath, array $symlinks): void
    {
        Helper::createHardlinks($basePath, $symlinks);
    }

    public static function createHardlink(string $basePath, string $link, string $target): void
    {
        Helper::createHardlink($basePath, $link, $target);
    }

    public static function createSymlinks(string $basePath, array $symlinks): void
    {
        Helper::createSymlinks($basePath, $symlinks);
    }

    public static function createSymlink(string $basePath, string $link, string $target): void
    {
        Helper::createSymlink($basePath, $link, $target);
    }

    public static function ensureParentDirectory(string|array $filenames): void
    {
        Helper::ensureParentDirectory($filenames);
    }

    public static function exists(string $path): bool
    {
        return Helper::exists($path);
    }

    public static function fileMode(string $path): int
    {
        return Helper::fileMode($path);
    }

    public static function mkdir(array|string $directories, ?string $basePath = null): void
    {
        Helper::mkdir($directories, $basePath);
    }

    public static function remove(string|iterable $paths): void
    {
        Helper::remove($paths);
    }

    public static function touch(string|array $paths, ?string $basePath = null): void
    {
        Helper::touch($paths, $basePath);
    }
}
