<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Tests\FileSyncer\Factory\PhpFileSyncerFactory;

/** @coversNothing */
final class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerFactoryClass(): string
    {
        return PhpFileSyncerFactory::class;
    }
}
