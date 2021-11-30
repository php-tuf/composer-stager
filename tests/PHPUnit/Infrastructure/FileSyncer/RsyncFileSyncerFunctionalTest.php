<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\FileSyncer\FileSyncerFunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Util\PathUtil
 */
class RsyncFileSyncerFunctionalTest extends FileSyncerFunctionalTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        if (!self::isRsyncAvailable()) {
            self::markTestSkipped('Rsync is not available for testing.');
        }
    }

    protected static function isRsyncAvailable(): bool
    {
        $finder = new SymfonyExecutableFinder();
        return $finder->find('rsync') !== null;
    }

    protected function createSut(): FileSyncerInterface
    {
        $container = self::getContainer();

        /** @var RsyncFileSyncer $sut */
        $sut = $container->get(RsyncFileSyncer::class);
        return $sut;
    }
}
