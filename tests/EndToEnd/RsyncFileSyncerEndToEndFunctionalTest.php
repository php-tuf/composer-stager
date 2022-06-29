<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;

/** @coversNothing */
final class RsyncFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (self::isRsyncAvailable()) {
            return;
        }

        self::markTestSkipped('Rsync is not available for testing.');
    }

    protected function fileSyncerClass(): string
    {
        return RsyncFileSyncer::class;
    }
}
