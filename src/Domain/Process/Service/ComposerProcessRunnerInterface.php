<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Process\Service;

/**
 * Runs Composer commands.
 *
 * @package Process
 *
 * @api
 */
interface ComposerProcessRunnerInterface extends ProcessRunnerInterface
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
     * @param \PhpTuf\ComposerStager\Domain\Process\Service\ProcessOutputCallbackInterface|null $callback
     *   An optional PHP callback to run whenever there is process output.
     * @param int|null $timeout
     *   An optional process timeout (maximum runtime) in seconds. Set to null
     *   to disable.
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the command process cannot be created due to host configuration.
     * @throws \PhpTuf\ComposerStager\Domain\Exception\RuntimeException
     *   If the operation fails.
     *
     * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
     */
    public function run(
        array $command,
        ?ProcessOutputCallbackInterface $callback = null,
        ?int $timeout = self::DEFAULT_TIMEOUT,
    ): void;
}
