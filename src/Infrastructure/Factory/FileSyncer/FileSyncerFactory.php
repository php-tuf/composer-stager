<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface;
use Symfony\Component\Process\ExecutableFinder;

/** @api */
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
