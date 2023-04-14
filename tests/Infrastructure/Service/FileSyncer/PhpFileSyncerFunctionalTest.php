<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath $source
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
