<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service;

use PhpTuf\ComposerStager\Domain\FileSyncer\Service\FileSyncerInterface;

/**
 * Provides a PHP-based file syncer.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package FileSyncer
 *
 * @api
 */
interface PhpFileSyncerInterface extends FileSyncerInterface
{
}
