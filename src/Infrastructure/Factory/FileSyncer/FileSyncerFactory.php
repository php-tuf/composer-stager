<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface;
use Symfony\Component\Process\ExecutableFinder;

/** This is for selecting and creating the appropriate file syncer for the host. */
final class FileSyncerFactory
{
    /** @var \Symfony\Component\Process\ExecutableFinder */
    private $executableFinder;

    /** @var \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncerInterface */
    private $phpFileSyncer;

    /** @var \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncerInterface */
    private $rsyncFileSyncer;

    public function __construct(
        ExecutableFinder $executableFinder,
        PhpFileSyncerInterface $phpFileSyncer,
        RsyncFileSyncerInterface $rsyncFileSyncer
    ) {
        $this->executableFinder = $executableFinder;
        $this->phpFileSyncer = $phpFileSyncer;
        $this->rsyncFileSyncer = $rsyncFileSyncer;
    }

    public function create(): FileSyncerInterface
    {
        if ($this->executableFinder->find('rsync') !== null) {
            return $this->rsyncFileSyncer;
        }

        return $this->phpFileSyncer;
    }
}
