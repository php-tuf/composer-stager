<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\ProcessOutputCallback;

use Symfony\Component\Process\Process;

/**
 * Receives streamed process output.
 *
 * This provides an interface for output callbacks accepted by domain classes.
 * It is designed for compatibility with the Symfony Process component.
 *
 * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
 *
 * @noinspection PhpUnused
 */
interface ProcessOutputCallbackInterface
{
    /**
     * Standard output (stdout).
     */
    public const OUT = Process::OUT;

    /**
     * Standard error (stderr).
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
