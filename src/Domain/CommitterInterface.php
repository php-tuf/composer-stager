<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;

/**
 * Makes the staged changes live by syncing the active directory with the staging directory.
 */
interface CommitterInterface
{
    /**
     * Commits staged changes to the active directory.
     *
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     * @param string $activeDir
     *   The active directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/public" or "public".
     * @param string[] $exclusions
     *   Paths to exclude, relative to the active directory. With rare exception,
     *   you should use the same exclusions when committing as when beginning.
     * @param \PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @see \PhpTuf\ComposerStager\Domain\BeginnerInterface::begin
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory or the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     *   If the active directory is not writable.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function commit(
        string $stagingDir,
        string $activeDir,
        array $exclusions = [],
        ?OutputCallbackInterface $callback = null,
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
