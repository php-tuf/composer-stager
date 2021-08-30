<?php

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer;

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
