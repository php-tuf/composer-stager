<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\Process;

use Symfony\Component\Process\Process;

/**
 * Creates Symfony Process objects.
 */
interface ProcessFactoryInterface
{
    /**
     * Creates a process object.
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
     *
     * @throws \PhpTuf\ComposerStager\Domain\Exception\LogicException
     *   If the process cannot be created.
     *
     * @see \Symfony\Component\Process\Process::__construct
     */
    public function create(array $command): Process;
}
