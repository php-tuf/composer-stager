<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\Factory\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;

/**
 * Creates file syncer objects.
 *
 * @api
 */
interface FileSyncerFactoryInterface
{
    /** Creates a file syncer. */
    public function create(): FileSyncerInterface;
}
