<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\FileSyncer\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;

/**
 * Provides a PHP-based file syncer.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package FileSyncer
 *
 * @internal Don't depend on this interface. It may be changed or removed at any time without notice.
 */
interface PhpFileSyncerInterface extends FileSyncerInterface
{
}
