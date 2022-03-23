<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Committer\Committer
 * @uses \PhpTuf\ComposerStager\Domain\Core\Stager\Stager
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveAndStagingDirsAreDifferent
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirExists
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\ActiveDirIsWritable
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\BeginnerPreconditions
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\CleanerPreconditions
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\CommitterPreconditions
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\CommonPreconditions
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\ComposerIsAvailable
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\StagerPreconditions
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirDoesNotExist
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirExists
 * @uses \PhpTuf\ComposerStager\Domain\Service\Precondition\StagingDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 */
class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
