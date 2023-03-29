<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Value\PathList;

/**
 * Handles a list of path strings.
 *
 * @package PathList
 *
 * @api
 */
interface PathListInterface
{
    /**
     * Returns all path strings as given, i.e., unresolved.
     *
     * @return array<string>
     */
    public function getAll(): array;

    /**
     * Adds a list of raw path strings.
     *
     * Path strings may be absolute or relative, e.g., "/var/www/example" or
     * "example". Nothing needs to actually exist at them.
     */
    public function add(string ...$paths): void;
}
