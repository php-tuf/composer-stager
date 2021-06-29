<?php

namespace PhpTuf\ComposerStager\Domain;

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
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If removal fails.
     */
    public function clean(string $stagingDir): void;

    /**
     * Determines whether or not the staging directory exists.
     *
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     */
    public function directoryExists(string $stagingDir): bool;
}
