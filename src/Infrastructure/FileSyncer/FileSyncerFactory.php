<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;
use Symfony\Component\Process\ExecutableFinder;

final class FileSyncerFactory implements FileSyncerFactoryInterface
{
    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface
     */
    private $phpFileSyncer;

    /**
     * @var \PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface
     */
    private $rsyncFileSyncer;

    public function __construct(
        ExecutableFinder $executableFinder,
        FileSyncerInterface $phpFileSyncer,
        FileSyncerInterface $rsyncFileSyncer
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
