<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;

/**
 * Removes the staging directory.
 */
interface CleanerInterface
{
    /**
     * Removes the staging directory.
     *
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
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
        string $stagingDir,
        ?OutputCallbackInterface $callback,
        ?int $timeout = 120
    ): void;

    /**
     * Determines whether the staging directory exists.
     *
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     */
    public function directoryExists(string $stagingDir): bool;
}
