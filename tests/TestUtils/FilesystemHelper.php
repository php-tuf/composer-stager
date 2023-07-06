<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path as SymfonyPath;

final class FilesystemHelper
{
    public static function createDirectories(array|string $directories, ?string $baseDir = null): void
    {
        // Convert $directories to an array if only a single string is given.
        if (is_string($directories)) {
            $directories = [$directories];
        }

        // If a base directory is provided, use it to make all directories absolute.
        if (is_string($baseDir)) {
            assert(SymfonyPath::isAbsolute($baseDir), 'Base directory must be absolute.');
            $directories = array_map(static fn ($dirname): string => SymfonyPath::makeAbsolute($dirname, $baseDir), $directories);
        }

        (new SymfonyFilesystem())->mkdir($directories);
    }

    public function delete(array $paths, ?string $baseDir = null): void
    {
        $paths = self::makeAbsolute($paths, $baseDir);
        (new SymfonyFilesystem())->remove($paths);
    }

    private static function makeAbsolute(array $paths, ?string $baseDir = null): array
    {
        return array_map(static fn ($dirname): string => SymfonyPath::makeAbsolute($dirname, $baseDir), $paths);
    }
}
