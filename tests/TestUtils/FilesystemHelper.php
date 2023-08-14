<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

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
            assert(PathHelper::isAbsolute($basePath), 'Base path must be absolute.');
            $directories = array_map(static fn ($dirname): string => PathHelper::makeAbsolute($dirname, $basePath), $directories);
        }

        (new SymfonyFilesystem())->mkdir($directories);
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
}
