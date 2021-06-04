<?php

namespace PhpTuf\ComposerStager\Console\Misc;

/**
 * Defines sysexits-compatible exit codes.
 *
 * After Symfony 5.2 these constants will be defined in
 * \Symfony\Component\Console\Command\Command. As long as we support 4.x, we
 * must supply them ourselves.
 *
 * @see https://tldp.org/LDP/abs/html/exitcodes.html
 *
 * @internal
 */
final class ExitCode
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
}
