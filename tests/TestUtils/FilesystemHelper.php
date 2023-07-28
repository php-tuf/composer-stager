<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;

final class FilesystemHelper
{
    public static function createDirectories(array|string $directories, ?string $basePath = null): void
    {
        // Convert $directories to an array if only a single string is given.
        if (is_string($directories)) {
            $directories = [$directories];
        }

        // If a base path is provided, use it to make all directories absolute.
        if (is_string($basePath)) {
            assert(SymfonyPath::isAbsolute($basePath), 'base path must be absolute.');
            $directories = array_map(static fn ($dirname): string => SymfonyPath::makeAbsolute($dirname, $basePath), $directories);
        }

        (new SymfonyFilesystem())->mkdir($directories);
    }

    public function delete(array $paths, ?string $basePath = null): void
    {
        $paths = self::makeAbsolute($paths, $basePath);
        (new SymfonyFilesystem())->remove($paths);
    }

    private static function makeAbsolute(array $paths, ?string $basePath = null): array
    {
        return array_map(static fn ($dirname): string => SymfonyPath::makeAbsolute($dirname, $basePath), $paths);
    }
}
