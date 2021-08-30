<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 */
class PhpFileSyncerTest extends AbstractFileSyncerTest
{
    protected const ACTIVE_DIR = '.';
    protected const STAGING_DIR = '.composer_staging';

    public static function setUpBeforeClass(): void
    {
        self::createTestEnvironment();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): FileSyncerInterface
    {
        $container = self::getContainer();

        /** @var \PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer $sut */
        $sut = $container->get(PhpFileSyncer::class);
        return $sut;
    }
}
