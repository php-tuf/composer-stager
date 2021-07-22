<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

/**
 * @internal
 *
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction:
 *
 * @see \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface
 * @see \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierFactoryInterface
 */
final class RsyncRunner extends AbstractRunner implements RsyncRunnerInterface
{
    protected $timeout = 300;

    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
