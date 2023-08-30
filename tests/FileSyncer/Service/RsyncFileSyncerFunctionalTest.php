<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\RsyncFileSyncer
 *
 * @group no_windows
 */
final class RsyncFileSyncerFunctionalTest extends FileSyncerFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return RsyncFileSyncer::class;
    }
}
