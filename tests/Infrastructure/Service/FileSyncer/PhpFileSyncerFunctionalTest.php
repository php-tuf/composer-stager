<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
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
