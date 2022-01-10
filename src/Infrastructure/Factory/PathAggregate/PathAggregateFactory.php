<?php

namespace PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate;

use PhpTuf\ComposerStager\Domain\Aggregate\PathAggregate\PathAggregateInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use TypeError;

/**
 * Creates a path aggregate.
*/
final class PathAggregateFactory
{
    /**
     * Creates a path aggregate from an array of strings.
     *
     * @param string[] $paths
     *   An array of path strings, as absolute or relative to the current
     *   working directory (CWD), e.g., "/var/www/example" or "example". Nothing
     *   needs to actually exist at the paths.
     *
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     */
    public static function create(array $paths): PathAggregateInterface
    {
        $paths = array_map(static function ($path): PathInterface {
            try {
                return PathFactory::create($path);
            } catch (TypeError $e) { // @phpstan-ignore-line Suppress false positive:
                // "TypeError is never thrown in the corresponding try block".
                throw new InvalidArgumentException(sprintf(
                    'Paths must be strings. Given %s.',
                    var_export($path, true)
                ), $e->getCode(), $e);
            }
        }, $paths);
        return new PathAggregate($paths);
    }
}
