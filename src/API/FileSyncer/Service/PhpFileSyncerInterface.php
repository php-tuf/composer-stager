<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\API\FileSyncer\Service;

/**
 * Provides a PHP-based file syncer.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package FileSyncer
 *
 * @api This interface is subject to our backward compatibility promise and may be safely depended upon.
 */
interface PhpFileSyncerInterface extends FileSyncerInterface
{
}
