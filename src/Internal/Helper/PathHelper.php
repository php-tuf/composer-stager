<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Helper;

use Symfony\Component\Filesystem\Path as SymfonyPath;

/**
 * @package Helper
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PathHelper implements PathHelperInterface
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
        // This is a static helper class. Prevent instantiation.
    }

    public static function canonicalize(string $path): string
    {
        $path = SymfonyPath::canonicalize($path);

        // SymfonyPath always uses forward slashes. Use the OS's
        // directory separator instead. And it doesn't reduce repeated
        // slashes after Windows drive names, so eliminate them, too.
        return (string) preg_replace('#/+#', DIRECTORY_SEPARATOR, $path);
    }

    public static function isAbsolute(string $path): bool
    {
        return SymfonyPath::isAbsolute($path);
    }
}
