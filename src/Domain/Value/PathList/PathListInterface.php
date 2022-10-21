<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Value\PathList;

/** Handles a list of path strings. */
interface PathListInterface
{
    /**
     * Returns all path strings as given, i.e., unresolved.
     *
     * @return array<string>
     */
    public function getAll(): array;

    /**
     * Adds an array of path strings to the list.
     *
     * Path strings may be absolute or relative, e.g., "/var/www/example" or
     * "example". Nothing needs to actually exist at them.
     *
     * @param array<string> $paths
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException
     */
    public function add(array $paths): void;
}
