<?php

namespace PhpTuf\ComposerStager\Infrastructure\Process\FileCopier;

use Symfony\Component\Process\ExecutableFinder;

/**
 * @internal
 */
final class FileCopierFactory implements FileCopierFactoryInterface
{
    /**
     * @var \Symfony\Component\Process\ExecutableFinder
     */
    private $executableFinder;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopierInterface
     */
    private $phpFileCopier;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopierInterface
     */
    private $rsyncFileCopier;

    public function __construct(
        ExecutableFinder $executableFinder,
        PhpFileCopierInterface $phpFileCopier,
        RsyncFileCopierInterface $rsyncFileCopier
    ) {
        $this->executableFinder = $executableFinder;
        $this->phpFileCopier = $phpFileCopier;
        $this->rsyncFileCopier = $rsyncFileCopier;
    }

    public function create(): FileCopierInterface
    {
        if ($this->executableFinder->find('rsync') !== null) {
            return $this->rsyncFileCopier;
        }
        return $this->phpFileCopier;
    }
}
