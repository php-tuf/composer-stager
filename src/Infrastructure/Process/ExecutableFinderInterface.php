<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

/**
 * Finds executables.
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
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the executable cannot be found.
     */
    public function find(string $name): string;
}
