<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Value\PathInterface;

/**
 * Creates path value objects.
 *
 * @package Path
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface PathFactoryInterface
{
    /**
     * Creates a path value object from a string.
     *
     * @param string $path
     *   The path string may be absolute or relative to the current working
     *   directory as returned by `getcwd()` at runtime, e.g., "/var/www/example"
     *   or "example". Nothing needs to actually exist at the path.
     * @param \PhpTuf\ComposerStager\API\Path\Value\PathInterface|null $cwd
     *   Optionally override the working directory used as the base for relative
     *   paths. Nothing needs to actually exist at the path. Therefore, it is
     *   simply assumed to represent a directory, as opposed to a file--even if
     *   it has an extension, which is no guarantee of type.
     */
    public static function create(string $path, ?PathInterface $cwd = null): PathInterface;
}
