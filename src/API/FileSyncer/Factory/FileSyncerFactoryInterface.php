<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;

/**
 * Creates file syncer objects.
 *
 * @package FileSyncer
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface FileSyncerFactoryInterface
{
    /** Creates a file syncer. */
    public function create(): FileSyncerInterface;
}
