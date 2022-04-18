<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;

/** @coversNothing */
final class RsyncFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!self::isRsyncAvailable()) {
            self::markTestSkipped('Rsync is not available for testing.');
        }

        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    protected function fileSyncerClass(): string
    {
        return RsyncFileSyncer::class;
    }
}
