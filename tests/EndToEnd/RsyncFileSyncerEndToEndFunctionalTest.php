<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;

/**
 * @coversNothing
 *
 * @group no_windows
 */
final class RsyncFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return RsyncFileSyncer::class;
    }
}
