<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Internal\FileSyncer\Service\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\API\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\API\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Internal\Core\Beginner
 * @uses \PhpTuf\ComposerStager\Internal\Core\Cleaner
 * @uses \PhpTuf\ComposerStager\Internal\Core\Committer
 * @uses \PhpTuf\ComposerStager\Internal\Core\Stager
 * @uses \PhpTuf\ComposerStager\Internal\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Internal\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Internal\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Internal\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveAndStagingDirsAreDifferent
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirExists
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsReady
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\ActiveDirIsWritable
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\BeginnerPreconditions
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\CleanerPreconditions
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\CommitterPreconditions
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\CommonPreconditions
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\ComposerIsAvailable
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\HostSupportsRunningProcesses
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoHardLinksExist
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoLinksExistOnWindows
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointOutsideTheCodebase
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\NoUnsupportedLinksExist
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\StagerPreconditions
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirDoesNotExist
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirExists
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsReady
 * @uses \PhpTuf\ComposerStager\Internal\Precondition\Service\StagingDirIsWritable
 * @uses \PhpTuf\ComposerStager\Internal\Process\Factory\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Internal\Process\Service\AbstractProcessRunner
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\DomainOptions
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Internal\Translation\Value\TranslationParameters
 */
final class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
