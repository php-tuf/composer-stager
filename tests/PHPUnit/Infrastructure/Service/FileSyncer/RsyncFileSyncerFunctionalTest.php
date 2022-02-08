<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::getRelativePath
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PathAggregate\PathAggregate
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
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

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer $sut */
        $sut = $container->get(RsyncFileSyncer::class);
        return $sut;
    }
}
