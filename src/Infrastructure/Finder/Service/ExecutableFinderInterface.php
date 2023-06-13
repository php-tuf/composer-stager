<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Finder\Service;

/**
 * Finds executables.
 *
 * @package Finder
 *
 * @api
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
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the executable cannot be found.
     */
    public function find(string $name): string;
}
