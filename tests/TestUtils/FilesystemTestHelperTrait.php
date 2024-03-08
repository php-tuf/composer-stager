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

    public static function createHardlinks(array $symlinks, ?string $basePath = null): void
    {
        Helper::createHardlinks($symlinks, $basePath);
    }

    public static function createHardlink(string $link, string $target, ?string $basePath = null): void
    {
        Helper::createHardlink($link, $target, $basePath);
    }

    /**
     * @param array<string, string> $symlinks
     *   An array of symlinks values, keyed by the link (source) path
     *   with a corresponding value of the link target path.
     */
    public static function createSymlinks(array $symlinks, ?string $basePath = null): void
    {
        Helper::createSymlinks($symlinks, $basePath);
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

    public static function rm(string|iterable $paths): void
    {
        Helper::rm($paths);
    }

    public static function touch(string|array $paths, ?string $basePath = null): void
    {
        Helper::touch($paths, $basePath);
    }
}
