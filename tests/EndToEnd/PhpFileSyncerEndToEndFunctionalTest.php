<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Domain\Core\Beginner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Cleaner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Committer
 * @uses \PhpTuf\ComposerStager\Domain\Core\Stager
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveAndStagingDirsAreDifferent
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ActiveDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\BeginnerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CleanerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CommitterPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\CommonPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\ComposerIsAvailable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoHardLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoLinksExistOnWindows
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointOutsideTheCodebase
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoSymlinksPointToADirectory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoUnsupportedLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirDoesNotExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Precondition\StagingDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\ProcessRunner\AbstractRunner
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\SymfonyTranslatorProxy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslationParameters
 */
final class PhpFileSyncerEndToEndFunctionalTest extends EndToEndFunctionalTestCase
{
    protected function fileSyncerClass(): string
    {
        return PhpFileSyncer::class;
    }
}
