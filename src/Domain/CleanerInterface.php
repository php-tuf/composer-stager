<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;

/**
 * Removes the staging directory.
 */
interface CleanerInterface
{
    /**
     * Removes the staging directory.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
     *   The staging directory.
     * @param \PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If removal fails.
     */
    public function clean(
        PathInterface $stagingDir,
        OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;

    /**
     * Determines whether the staging directory exists.
     *
     * @param \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $stagingDir
     *   The staging directory.
     */
    public function directoryExists(PathInterface $stagingDir): bool;
}
