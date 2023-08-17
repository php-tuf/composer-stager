<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Service;

/**
 * Runs Composer commands.
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface ComposerProcessRunnerInterface
{
    /**
     * Runs a given Composer command.
     *
     * @param array<string> $command
     *   The command to run and its arguments as separate string values. Example:
     *   ```php
     *   $command = [
     *       // "composer" is implied.
     *       'require',
     *       'example/package:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     * @param \PhpTuf\ComposerStager\API\Process\Service\OutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int $timeout
     *   An optional process timeout (maximum runtime) in seconds. If set to
     *   zero (0), no time limit is imposed.
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the command process cannot be created due to host configuration.
     * @throws \PhpTuf\ComposerStager\API\Exception\RuntimeException
     *   If the operation fails.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     */
    public function run(
        array $command,
        ?OutputCallbackInterface $callback = null,
        int $timeout = ProcessInterface::DEFAULT_TIMEOUT,
    ): void;
}
