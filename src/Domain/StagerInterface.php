<?php

namespace PhpTuf\ComposerStager\Domain;

use PhpTuf\ComposerStager\Domain\Output\CallbackInterface;

/**
 * Executes a Composer command in the staging directory.
 */
interface StagerInterface
{
    /**
     * @param string[] $composerCommand
     *   The Composer command parts exactly as they would be typed in the terminal.
     *   There's no need to escape them in any way, only to separate them. Example:
     *
     *   ```php
     *   $command = [
     *       // "composer" is implied.
     *       'require',
     *       'lorem/ipsum:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     *
     * @param string $stagingDir
     *   The staging directory as an absolute path or relative to the working
     *   directory (CWD), e.g., "/var/www/staging" or "staging".
     * @param \PhpTuf\ComposerStager\Domain\Output\CallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     *
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     *   If the staging directory is not found.
     * @throws \PhpTuf\ComposerStager\Exception\DirectoryNotWritableException
     *   If the staging directory is not writable.
     * @throws \PhpTuf\ComposerStager\Exception\InvalidArgumentException
     *   If the given Composer command is invalid.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function stage(array $composerCommand, string $stagingDir, ?CallbackInterface $callback = null): void;
}
