<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $source
 */
final class PhpFileSyncerFunctionalTest extends FileSyncerFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }

    /** @coversNothing */
    public function testSyncWithDirectorySymlinks(): void
    {
        // @todo PHPFileSyncer does not yet support symlinks to directories.
        //   https://github.com/php-tuf/composer-stager/issues/99
        $this->expectNotToPerformAssertions();
    }
}
