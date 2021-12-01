<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\Runner;

use PhpTuf\ComposerStager\Domain\Process\Runner\RsyncRunnerInterface;

/**
 * @internal
 *
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction:
 *
 * @see \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface
 * @see \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerFactoryInterface
 */
final class RsyncRunner extends AbstractRunner implements RsyncRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
