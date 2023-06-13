<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;

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
