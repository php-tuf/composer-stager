<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 */
final class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
