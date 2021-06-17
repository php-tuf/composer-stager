<?php

namespace PhpTuf\ComposerStager\Domain\Output;

use Symfony\Component\Process\Process;

/**
 * Receives streamed process output.
 *
 * This provides an interface for output callbacks accepted by domain classes.
 * It ensures compatibility with the Symfony Process component used in the
 * infrastructure layer.
 *
 * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
 */
interface CallbackInterface
{
    /**
     * Standard output (stdout).
     *
     * @var string
     */
    public const OUT = Process::OUT;

    /**
     * Standard error (stderr).
     *
     * @var string
     */
    public const ERR = Process::ERR;

    /**
     * @param string $type
     *   The output type. Possible values are ::OUT for standard output (stdout)
     *   and ::ERR for standard error (stderr).
     * @param string $buffer
     *   A line of output.
     *
     * @see \Symfony\Component\Process\Process::readPipes
     */
    public function __invoke(string $type, string $buffer): void;
}
