<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

/**
 * @internal
 *
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction:
 *
 * @see \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier
 */
final class RsyncRunner extends AbstractRunner implements RsyncRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
