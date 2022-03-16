<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd\EndToEndFunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder as SymfonyExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 * @covers ::__construct
 * @covers ::getRelativePath
 * @covers ::isDescendant
 * @covers ::sync
 * @uses \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Committer\Committer
 * @uses \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 */
class RsyncFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return RsyncFileSyncer::class;
    }

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
        parent::setUp();
    }

    protected static function isRsyncAvailable(): bool
    {
        $finder = new SymfonyExecutableFinder();
        return $finder->find('rsync') !== null;
    }
}
