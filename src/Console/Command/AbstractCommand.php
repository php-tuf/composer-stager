<?php

namespace PhpTuf\ComposerStager\Console\Command;

use Symfony\Component\Console\Command\Command;

/**
 * @internal
 */
abstract class AbstractCommand extends Command
{
    // sysexits-compatible exit codes.
    // See https://tldp.org/LDP/abs/html/exitcodes.html
    // @todo As of 5.2 these are defined in \Symfony\Component\Console\Command\Command.
    //   Remove them once we drop support for Symfony 4.x.
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;
}
