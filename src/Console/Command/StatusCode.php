<?php

namespace PhpTuf\ComposerStager\Console\Command;

/**
 * Defines sysexits-compatible status codes.
 *
 * @see https://www.freebsd.org/cgi/man.cgi?query=sysexits
 */
class StatusCode
{
    public const OK = 0;
    public const ERROR = 1;
    public const USER_CANCEL = 75;
}
