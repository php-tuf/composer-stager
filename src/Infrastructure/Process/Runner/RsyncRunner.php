<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner;

/**
 * @internal
 */
class RsyncRunner extends AbstractRunner
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
