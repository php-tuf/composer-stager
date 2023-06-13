<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service\PhpFileSyncer;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service\PhpFileSyncer
 *
 * @covers \PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Domain\Core\Beginner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Cleaner
 * @uses \PhpTuf\ComposerStager\Domain\Core\Committer
 * @uses \PhpTuf\ComposerStager\Domain\Core\Stager
 * @uses \PhpTuf\ComposerStager\Domain\Exception\PreconditionException
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Service\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Finder\Service\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Finder\Service\FileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Host\Service\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Factory\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\PathList
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Path\Value\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractFileIteratingPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPrecondition
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\AbstractPreconditionsTree
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveAndStagingDirsAreDifferent
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ActiveDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\BeginnerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\CleanerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\CommitterPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\CommonPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\ComposerIsAvailable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoAbsoluteSymlinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoHardLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoLinksExistOnWindows
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoSymlinksPointOutsideTheCodebase
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoSymlinksPointToADirectory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\NoUnsupportedLinksExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagerPreconditions
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirDoesNotExist
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirExists
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirIsReady
 * @uses \PhpTuf\ComposerStager\Infrastructure\Precondition\Service\StagingDirIsWritable
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\Factory\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\ProcessRunner\Service\AbstractRunner
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
