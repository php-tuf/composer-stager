<?php

namespace PhpTuf\ComposerStager\Domain\FileSyncer;

use PhpTuf\ComposerStager\Domain\FileSyncer\FileSyncerInterface;

/**
 * Creates file syncers.
 */
interface FileSyncerFactoryInterface
{
    /**
     * Creates the correct file syncer given available tools, e.g., rsync.
     */
    public function create(): FileSyncerInterface;
}
