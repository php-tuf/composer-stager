<?php

namespace PhpTuf\ComposerStager\Domain;

/**
 * Begins the staging process by copying the active directory to the staging directory.
 */
interface BeginnerInterface
{
    /**
     * Begins the staging process.
     *
     * @param string $activeDir
     *   The path to the active directory, absolute or relative to the working
     *   directory (CWD), e.g., "/var/www/public" or "public".
     * @param string $stagingDir
     *   The path to the staging directory, absolute or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     * @param callable|null $callback
     *   An optional PHP callback to run whenever there is some output available
     *   on STDOUT or STDERR. Example:
     *
     *   ```php
     *   $callback = function (string $type, string $buffer) {
     *       if ($type === \PhpTuf\ComposerStager\Domain\OutputType::ERR) {
     *           echo 'ERR > ' . $buffer;
     *       }
     *       if ($type === \PhpTuf\ComposerStager\Domain\OutputType::OUT) {
     *           echo 'OUT > ' . $buffer;
     *       }
     *   }
     *   ```
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryAlreadyExistsException
     *   If the staging directory already exists.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the active directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function begin(string $activeDir, string $stagingDir, ?callable $callback = null): void;
}
