<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer\FileSyncerFunctionalTestCase;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Util\PathUtil
 */
class PhpFileSyncerFunctionalTest extends FileSyncerFunctionalTestCase
{
    protected function createSut(): FileSyncerInterface
    {
        $container = self::getContainer();

        /** @var \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer $sut */
        $sut = $container->get(PhpFileSyncer::class);
        return $sut;
    }
}
