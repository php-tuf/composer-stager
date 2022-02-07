<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\Path;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath;
use PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath;

/**
 * Creates path value objects.
 */
final class PathFactory
{
    /**
     * Creates a path value object from a string.
     *
     * @param string $path
     *   The path string, as absolute or relative to the current working directory
     *   as returned by `getcwd()` at runtime, e.g., "/var/www/example" or
     *   "example". Nothing needs to actually exist at the path.
     */
    public static function create(string $path): PathInterface
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return new WindowsPath($path); // @codeCoverageIgnore
        }
        return new UnixLikePath($path);
    }
}
