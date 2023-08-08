<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Value\Path;

/**
 * @package Path
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final class PathFactory implements PathFactoryInterface
{
    public static function create(string $path, ?PathInterface $basePath = null): PathInterface
    {
        return new Path($path, $basePath);
    }
}
