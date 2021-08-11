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
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopierInterface
     */
    private $rsyncFileCopier;

    /**
     * @var \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopierInterface
     */
    private $symfonyFileCopier;

    public function __construct(
        ExecutableFinder $executableFinder,
        RsyncFileCopierInterface $rsyncFileCopier,
        SymfonyFileCopierInterface $symfonyFileCopier
    ) {
        $this->executableFinder = $executableFinder;
        $this->rsyncFileCopier = $rsyncFileCopier;
        $this->symfonyFileCopier = $symfonyFileCopier;
    }

    public function create(): FileCopierInterface
    {
        if ($this->executableFinder->find('rsync') !== null) {
            return $this->rsyncFileCopier;
        }
        return $this->symfonyFileCopier;
    }
}
