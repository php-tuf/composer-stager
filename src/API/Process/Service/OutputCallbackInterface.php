<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\Process\Service;

/**
 * Receives streamed process output.
 *
 * This provides an interface for output callbacks accepted by API classes.
 * It is designed for compatibility with the Symfony Process component.
 *
 * @see https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
 *
 * @package Process
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 *
 * @noinspection PhpUnused
 */
interface OutputCallbackInterface
{
    /** Standard output (stdout). */
    public const OUT = 'OUT';

    /** Standard error (stderr). */
    public const ERR = 'ERR';

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
