<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Domain\Service\FileSyncer;

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
