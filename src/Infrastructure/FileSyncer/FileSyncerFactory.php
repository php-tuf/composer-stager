<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

use Symfony\Component\Process\ExecutableFinder;

/**
 * @internal
 */
final class FileSyncerFactory implements FileSyncerFactoryInterface
{
    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncerInterface
     */
    private $phpFileSyncer;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncerInterface
     */
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
