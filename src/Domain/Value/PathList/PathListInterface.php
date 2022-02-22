<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Value\PathList;

/**
 * Handles a list of path strings.
 */
interface PathListInterface
{
    /**
     * Returns all path strings.
     *
     * @return string[]
     */
    public function getAll(): array;
}
