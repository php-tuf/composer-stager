<?php

namespace PhpTuf\ComposerStager\Domain\Process\Runner;

use PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface;

/**
 * Runs Composer commands.
 */
interface ComposerRunnerInterface
{
    /**
     * Runs a given Composer command.
     *
     * @param string[] $command
     *   The command to run and its arguments as separate string values. Example:
     *   ```php
     *   $command = [
     *       // "composer" is implied.
     *       'require',
     *       'lorem/ipsum:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     * @param \PhpTuf\ComposerStager\Domain\Process\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     *
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     *   If the executable cannot be found.
     * @throws \PhpTuf\ComposerStager\Exception\LogicException
     *   If the command process cannot be created.
     * @throws \PhpTuf\ComposerStager\Exception\ProcessFailedException
     *   If the command process doesn't terminate successfully.
     */
    public function run(
        array $command,
        ?OutputCallbackInterface $callback = null,
        ?int $timeout = 120
    ): void;
}
