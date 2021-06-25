<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

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
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory or the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     *   If the active directory is not writable.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     *
     */
    public function commit(string $stagingDir, string $activeDir, ?ProcessOutputCallbackInterface $callback = null): void;
}
