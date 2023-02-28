<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface;

/** Runs rsync commands. */
interface RsyncRunnerInterface extends ProcessRunnerInterface
{
    /**
     * Runs a given rsync command.
     *
     * @param array<string> $command
     *   The command to run and its arguments as separate string values. Example:
     *   ```php
     *   $command = [
     *       // "rsync" is implied.
     *       '--recursive',
     *       'path/to/source',
     *       'path/to/destination',
     *   ];
     *   ```
     * @param \PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback\ProcessOutputCallbackInterface|null $callback
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
