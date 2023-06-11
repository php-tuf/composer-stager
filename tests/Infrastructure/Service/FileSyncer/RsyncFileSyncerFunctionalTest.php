<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\Factory\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\RsyncFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
 *
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Path\Value\TestPath $source
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
