<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface;

/**
 * Runs Composer commands.
 */
interface ComposerRunnerInterface
{
    /**
     * Runs a given command.
     *
     * @param string[] $command
     *   The command to run and its arguments as separate string values. Example:
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
     * @param \PhpTuf\ComposerStager\Domain\Output\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
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
    public function run(array $command, ?ProcessOutputCallbackInterface $callback = null): void;
}
