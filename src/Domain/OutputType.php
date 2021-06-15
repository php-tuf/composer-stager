<?php

namespace PhpTuf\ComposerStager\Domain;

/**
 * Process output type constants.
 *
 * @see \Symfony\Component\Process\Process::readPipes
 */
final class OutputType
{
    /**
     * @var string
     */
    public const OUT = \Symfony\Component\Process\Process::OUT;

    /**
     * @var string
     */
    public const ERR = \Symfony\Component\Process\Process::ERR;
}
