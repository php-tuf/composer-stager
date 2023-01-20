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
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\BeginnerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CleanerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommitterPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\CommonPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\NoUnsupportedLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Aggregate\PreconditionsTree\StagingDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractLinkIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CodeBaseContainsNoSymlinks
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirDoesNotExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 */
final class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
