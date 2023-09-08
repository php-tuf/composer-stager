<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\RsyncFileSyncerInterface;

final class RsyncFileSyncerFactory implements FileSyncerFactoryInterface
{
    public function __construct(private readonly RsyncFileSyncerInterface $rsyncFileSyncer)
    {
    }

    public function create(): RsyncFileSyncerInterface
    {
        return $this->rsyncFileSyncer;
    }
}
