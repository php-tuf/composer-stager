<?php

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Domain\Core;

use PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner;
use PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner;
use PhpTuf\ComposerStager\Domain\Core\Committer\Committer;
use PhpTuf\ComposerStager\Domain\Core\Stager\Stager;
use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Factory\PathAggregate\PathAggregateFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 *
 * @property \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner beginner
 * @property \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner cleaner
 * @property \PhpTuf\ComposerStager\Domain\Core\Committer\Committer committer
 * @property \PhpTuf\ComposerStager\Domain\Core\Stager\Stager stager
 * @property \PhpTuf\ComposerStager\Infrastructure\Service\Finder\ExecutableFinder executableFinder
 */
class EndToEndFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        $container = self::getContainer();
        $this->beginner = $container->get(Beginner::class);
        $this->stager = $container->get(Stager::class);
        $this->committer = $container->get(Committer::class);
        $this->cleaner = $container->get(Cleaner::class);
        $this->executableFinder = $container->get(ExecutableFinder::class);

        self::removeTestEnvironment();
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    /**
     * Note: This test uses a relatively simple fixture (i.e., test directory
     * contents), because nothing more complicated is needed to exercise the domain
     * classes at their level of abstraction. The underlying file syncers are
     * tested with many more complicated scenarios, as appropriate to their level.
     *
     * @see \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer\FileSyncerFunctionalTestCase
     */
    public function test(): void
    {
        $activeDir = PathFactory::create(self::ACTIVE_DIR);
        $stagingDir = PathFactory::create(self::STAGING_DIR);

        // Create fixture (active directory).
        self::createFiles(self::ACTIVE_DIR, [
            'UNCHANGING_file.txt',
            'EXCLUDED_file.txt',
            'CHANGE_in_staging_dir.txt',
            'DELETE_from_staging_dir.txt',
        ]);

        // Create composer.json.
        $this->composer([
            'init',
            '--name=original/name',
        ], $activeDir);

        $exclusions = PathAggregateFactory::create(['EXCLUDED_file.txt']);

        // Begin.
        $this->beginner->begin($activeDir, $stagingDir, $exclusions);

        self::assertDirectoryListing(self::STAGING_DIR, [
            'UNCHANGING_file.txt',
            'CHANGE_in_staging_dir.txt',
            'DELETE_from_staging_dir.txt',
            'composer.json',
        ], '', 'Synced correct files from active directory to new staging directory.');

        // Stage a Composer command (that doesn't make any HTTP requests).
        $newComposerName = 'new/name';
        $this->stager->stage([
            'config',
            'name',
            $newComposerName,
        ], $stagingDir);

        // Simulate some Composer changes.
        self::changeFile(self::STAGING_DIR, 'CHANGE_in_staging_dir.txt');
        self::deleteFile(self::STAGING_DIR, 'DELETE_from_staging_dir.txt');

        // Commit.
        $this->committer->commit($stagingDir, $activeDir, $exclusions);

        self::assertDirectoryListing(self::ACTIVE_DIR, [
            'UNCHANGING_file.txt',
            'EXCLUDED_file.txt',
            'CHANGE_in_staging_dir.txt',
            'composer.json',
        ], '', 'Synced correct files from staging directory back to active directory.');

        self::assertSame($newComposerName, $this->composer([
            'config',
            'name',
        ], $activeDir), 'Synced staged Composer changes back to active directory.');

        // Assert unchanged file contents.
        self::assertFileNotChanged(self::ACTIVE_DIR, 'UNCHANGING_file.txt');
        self::assertFileNotChanged(self::ACTIVE_DIR, 'EXCLUDED_file.txt');

        // Assert changed file contents.
        self::assertFileChanged(self::ACTIVE_DIR, 'CHANGE_in_staging_dir.txt');

        // Clean.
        $this->cleaner->clean($stagingDir);

        self::assertFileDoesNotExist($stagingDir->resolve(), 'Staging directory was completely removed.');
    }

    private function composer(array $command, PathInterface $cwd): string
    {
        $composer = $this->executableFinder->find('composer');
        array_unshift($command, $composer);
        $command[] = '--no-interaction';

        $process = new Process($command, $cwd->resolve());
        $process->mustRun();
        return rtrim($process->getOutput());
    }
}
