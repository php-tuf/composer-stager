<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\API\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
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
