<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

use PhpTuf\ComposerStager\API\Process\Service\RsyncProcessRunnerInterface;

/**
 * Before using this class outside the internal layer, consider a
 * higher-level abstraction, e.g.:
 *
 * @see \PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface
 * @see \PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactoryInterface
 *
 * @package Process
 *
 * @internal Don't instantiate this class directly. Get it from the service container via its interface.
 */
final class RsyncProcessProcessRunner extends AbstractProcessRunner implements RsyncProcessRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
