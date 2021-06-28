<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process;

/**
 * Finds executables.
 */
interface ExecutableFinderInterface
{
    /**
     * @param string $name
     *   The machine name of the executable, e.g., "composer" or "rsync".
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the executable cannot be found.
     */
    public function find(string $name): string;
}
