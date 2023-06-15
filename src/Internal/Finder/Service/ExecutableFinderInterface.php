<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Finder\Service;

/**
 * Finds executables.
 *
 * @package Finder
 *
 * @internal Don't depend on this interface. It may be changed or removed at any time without notice.
 */
interface ExecutableFinderInterface
{
    /**
     * Finds the path to a given executable.
     *
     * @param string $name
     *   The machine name of the executable, e.g., "composer" or "rsync".
     *
     * @return string
     *   The path to the given executable.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the executable cannot be found.
     */
    public function find(string $name): string;
}
