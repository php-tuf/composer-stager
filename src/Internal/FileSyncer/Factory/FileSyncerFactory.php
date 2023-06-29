<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @package FileSyncer
 *
 * @internal Don't depend directly on this class. It may be changed or removed at any time without notice.
 */
final class FileSyncerFactory implements FileSyncerFactoryInterface
{
    public function __construct(
        private readonly ExecutableFinder $executableFinder,
        private readonly PhpFileSyncerInterface $phpFileSyncer,
        private readonly RsyncFileSyncerInterface $rsyncFileSyncer,
    ) {
    }

    public function create(): FileSyncerInterface
    {
        if ($this->executableFinder->find('rsync') !== null) {
            return $this->rsyncFileSyncer;
        }

        return $this->phpFileSyncer;
    }
}
