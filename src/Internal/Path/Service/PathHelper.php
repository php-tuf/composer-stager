<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Service;

use PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableAwareTrait;
use Symfony\Component\Filesystem\Path as SymfonyPath;

/**
 * @package Path
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PathHelper implements PathHelperInterface
{
    use TranslatableAwareTrait;

    public function canonicalize(string $path): string
    {
        $path = SymfonyPath::canonicalize($path);

        // Eliminate repeated slashes after Windows drive names.
        $canonicalized = preg_replace('#/+#', '/', $path);

        assert(is_string($canonicalized));

        return $canonicalized;
    }

    public function isAbsolute(string $path): bool
    {
        return SymfonyPath::isAbsolute($path);
    }

    public function isDescendant(string $descendant, string $ancestor): bool
    {
        $ancestor .= '/';

        return str_starts_with($descendant, $ancestor);
    }

    public function isRelative(string $path): bool
    {
        return SymfonyPath::isRelative($path);
    }
}
