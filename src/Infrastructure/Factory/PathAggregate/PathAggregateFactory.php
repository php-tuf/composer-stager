<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;

/**
 * Creates path aggregates.
*/
final class PathAggregateFactory
{
    /**
     * Creates a path aggregate from an array of strings.
     *
     * @param string[]|mixed[] $paths
     *   An array of path strings, as absolute or relative to the current working
     *   directory as returned by `getcwd()` at runtime, e.g., "/var/www/example"
     *   or "example". Nothing needs to actually exist at the paths.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    public static function create(array $paths): PathAggregateInterface
    {
        $paths = array_map(static function ($path): PathInterface {
            if (!is_string($path)) {
                throw new InvalidArgumentException(sprintf(
                    'Paths must be strings. Given %s.',
                    var_export($path, true)
                ));
            }
            return PathFactory::create($path);
        }, $paths);
        return new PathAggregate($paths);
    }
}
