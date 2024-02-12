<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Factory;

use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Creates Symfony Process objects.
 *
 * @package Process
 *
 * @internal Don't depend directly on this interface. It may be changed or removed at any time without notice.
 */
interface SymfonyProcessFactoryInterface
{
    /**
     * Creates a symfony process object.
     *
     * @param array<string> $command
     *   The command to run and its arguments listed as separate entries. Example:
     *   ```php
     *   $command = [
     *       'composer',
     *       'require',
     *       'example/package:"^1 || ^2"',
     *       '--with-all-dependencies',
     *   ];
     *   ```
     * @param array<string|\Stringable> $env
     *   An array of environment variables, keyed by variable name with corresponding
     *   string or stringable values. In addition to those explicitly specified,
     *   environment variables set on your system will be inherited. You can
     *   prevent this by setting to `false` variables you want to remove. Example:
     *   ```php
     *   $process->setEnv(
     *       'STRING_VAR' => 'a string',
     *       'STRINGABLE_VAR' => new StringableObject(),
     *       'REMOVE_ME' => false,
     *   );
     *   ```
     *
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the process cannot be created due to host configuration.
     *
     * @see \Symfony\Component\Process\Process::__construct
     */
    public function create(array $command, array $env = []): SymfonyProcess;
}
