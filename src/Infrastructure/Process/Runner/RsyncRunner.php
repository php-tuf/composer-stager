<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner;

/**
 * @internal
 *
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction:
 *
 * @see \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier
 */
class RsyncRunner extends AbstractRunner
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
