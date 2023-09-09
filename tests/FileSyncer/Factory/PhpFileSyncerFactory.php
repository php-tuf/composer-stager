<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Factory;

use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\API\FileSyncer\Service\PhpFileSyncerInterface;

final class PhpFileSyncerFactory implements FileSyncerFactoryInterface
{
    public function __construct(private readonly PhpFileSyncerInterface $phpFileSyncer)
    {
    }

    public function create(): PhpFileSyncerInterface
    {
        return $this->phpFileSyncer;
    }
}
