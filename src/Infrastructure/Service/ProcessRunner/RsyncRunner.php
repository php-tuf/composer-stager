<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;

/**
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction, e.g.:
 *
 * @see \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerFactoryInterface
 * @see \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface
 */
final class RsyncRunner extends AbstractRunner implements RsyncRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
