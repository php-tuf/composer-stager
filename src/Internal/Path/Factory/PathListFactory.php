<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Path\Factory;

use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathList;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * @package Path
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class PathListFactory implements PathListFactoryInterface
{
    public function create(string ...$paths): PathListInterface
    {
        return new PathList(...$paths);
    }
}
