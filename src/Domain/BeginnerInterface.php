<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\CallbackInterface;

/**
 * Begins the staging process by copying the active directory to the staging directory.
 */
interface BeginnerInterface
{
    /**
     * Begins the staging process.
     *
     * @param string $activeDir
     *   The active directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/public" or "public".
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     * @param \PhpTuf\ComposerStager\Domain\Output\CallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
     *   If the staging directory already exists.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function begin(string $activeDir, string $stagingDir, ?CallbackInterface $callback = null): void;
}
