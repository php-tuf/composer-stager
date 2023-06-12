<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner;

use PhpTuf\ComposerStager\Domain\Service\ProcessRunner\RsyncRunnerInterface;

/**
 * Before using this class outside the infrastructure layer, consider a
 * higher-level abstraction, e.g.:
 *
 * @see \PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface
 * @see \PhpTuf\ComposerStager\Infrastructure\FileSyncer\Factory\FileSyncerFactoryInterface
 *
 * @package ProcessRunner
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class RsyncRunner extends AbstractRunner implements RsyncRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
