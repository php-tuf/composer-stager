<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Selects and creates the appropriate file syncer for the host.
 *
 * @api
 */
final class FileSyncerFactory
{
    public function __construct(
        private readonly ExecutableFinder $executableFinder,
        private readonly PhpFileSyncerInterface $phpFileSyncer,
        private readonly RsyncFileSyncerInterface $rsyncFileSyncer,
    ) {
    }

    /** Creates the appropriate file syncer. */
    public function create(): FileSyncerInterface
    {
        if ($this->executableFinder->find('rsync') !== null) {
            return $this->rsyncFileSyncer;
        }

        return $this->phpFileSyncer;
    }
}
