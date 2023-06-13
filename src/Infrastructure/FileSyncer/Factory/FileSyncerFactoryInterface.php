<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer\Factory;

use PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface;

/**
 * Creates file syncer objects.
 *
 * @package FileSyncer
 *
 * @api
 */
interface FileSyncerFactoryInterface
{
    /** Creates a file syncer. */
    public function create(): FileSyncerInterface;
}
