<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Tests\FileSyncer\Factory\RsyncFileSyncerFactory;

/**
 * @coversNothing
 *
 * @group no_windows
 */
final class RsyncFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerFactoryClass(): string
    {
        return RsyncFileSyncerFactory::class;
    }
}
