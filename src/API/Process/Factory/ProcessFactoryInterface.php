<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Factory;

use PhpTuf\ComposerStager\API\Process\Service\ProcessInterface;

/**
 * Creates process objects.
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
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
     * @throws \PhpTuf\ComposerStager\API\Exception\LogicException
     *   If the process cannot be created due to host configuration.
     */
    public function create(array $command): ProcessInterface;
}
