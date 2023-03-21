<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner;
use PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner;
use PhpTuf\ComposerStager\Domain\Core\Committer\Committer;
use PhpTuf\ComposerStager\Domain\Core\Stager\Stager;
use PhpTuf\ComposerStager\Domain\Exception\PreconditionException;
use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Precondition\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * Provides a base for end-to-end functional tests, including the domain and
 * infrastructure layers. The test cases themselves are supplied by this class.
 * Subclasses specify the file syncer to use via ::fileSyncerClass().
 *
 * @property \PhpTuf\ComposerStager\Domain\Core\Beginner\Beginner $beginner
 * @property \PhpTuf\ComposerStager\Domain\Core\Cleaner\Cleaner $cleaner
 * @property \PhpTuf\ComposerStager\Domain\Core\Committer\Committer $committer
 * @property \PhpTuf\ComposerStager\Domain\Core\Stager\Stager $stager
 */
abstract class EndToEndFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        $container = $this->getContainer();

        // Override the FileSyncer implementation.
        $fileSyncer = $container->getDefinition(FileSyncerInterface::class);
        $fileSyncer->setFactory(null);
        $fileSyncer->setClass($this->fileSyncerClass());
        $container->setDefinition(FileSyncerInterface::class, $fileSyncer);

        // Compile the container.
        $container->compile();

        // Get services.
        $this->beginner = $container->get(Beginner::class);
        $this->stager = $container->get(Stager::class);
        $this->committer = $container->get(Committer::class);
        $this->cleaner = $container->get(Cleaner::class);

        // Create the test environment.
        self::createTestEnvironment(self::ACTIVE_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    /**
     * Specifies the file syncer implementation to use, e.g.,
     * \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer::class.
     */
    abstract protected function fileSyncerClass(): string;

    /** @dataProvider providerDirectories */
    public function testSync(string $activeDir, string $stagingDir): void
    {
        $activeDirPath = PathFactory::create($activeDir);
        $stagingDirPath = PathFactory::create($stagingDir);

        // Create fixture (active directory).
        self::createFiles($activeDir, [
            // Unchanging files.
            'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt',
            'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt',
            'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt',
            'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt',
            'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
            // Files excluded by exact pathname.
            'EXCLUDED_file_in_active_dir_root.txt',
            'arbitrary_subdir/EXCLUDED_file.txt',
            // Files excluded by directory.
            'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            'another_EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            'arbitrary_subdir/with/nested/EXCLUDED_dir/with/a/file/in/it/that/is/NEVER/CHANGED/anywhere.txt',
            // Files excluded by "hidden" directory, i.e., beginning with a "dot" (.), e.g., ".git".
            '.hidden_EXCLUDED_dir/one.txt',
            '.hidden_EXCLUDED_dir/two.txt',
            '.hidden_EXCLUDED_dir/three.txt',
            // Files to be changed in the staging directory.
            'CHANGE_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            // Files to be deleted from the staging directory.
            'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt',
            // Excluded file to be deleted from the ACTIVE directory after syncing to the staging directory.
            'another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt',
        ]);

        $arbitrarySymlinkTarget = 'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt';
        self::createSymlinks($activeDirPath->resolved(), [
            'EXCLUDED_symlink_in_active_dir_root.txt' => $arbitrarySymlinkTarget,
            'EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt' => $arbitrarySymlinkTarget,
        ]);

        // Create initial composer.json. (Doing so manually can be up to one
        // third (1/3) faster than actually using Composer.)
        self::putJson(
            $activeDir . '/composer.json',
            ['name' => 'original/name'],
        );

        $exclusions = [
            // Exact pathnames.
            'EXCLUDED_file_in_active_dir_root.txt',
            'EXCLUDED_symlink_in_active_dir_root.txt',
            'arbitrary_subdir/EXCLUDED_file.txt',
            // Directories.
            'EXCLUDED_dir',
            'arbitrary_subdir/with/nested/EXCLUDED_dir',
            // Directory with a trailing slash.
            'another_EXCLUDED_dir/',
            // "Hidden" directory.
            '.hidden_EXCLUDED_dir',
            // Duplicative.
            'EXCLUDED_file_in_active_dir_root.txt',
            // Overlapping.
            'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            // Non-existent.
            'file_that_NEVER_EXISTS_anywhere.txt',
        ];
        $exclusions = new PathList($exclusions);

        // Confirm that the beginner fails with unsupported symlinks present in the codebase.
        $preconditionMet = true;

        try {
            // Invoke the beginner without exclusions to cause it to find symlinks in the active directory.
            $this->beginner->begin($activeDirPath, $stagingDirPath);
        } catch (PreconditionException $e) {
            $failedPrecondition = $e->getPrecondition();
            $preconditionMet = false;
            self::assertEquals(NoAbsoluteSymlinksExist::class, $failedPrecondition::class, 'Correct "codebase contains symlinks" unmet.');
        } finally {
            assert(filetype($activeDirPath->resolved() . '/EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt') === 'link', 'An actual symlink is present in the codebase.');
            self::assertFalse($preconditionMet, 'Beginner fails with symlinks present in the codebase.');
        }

        // Begin: Sync files from the active directory to the new staging directory.
        $this->beginner->begin($activeDirPath, $stagingDirPath, $exclusions);

        $expectedStagingDirListing = [
            'composer.json',
            'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt',
            'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt',
            'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt',
            'CHANGE_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
        ];
        self::assertDirectoryListing($stagingDirPath->resolved(), $expectedStagingDirListing, '', sprintf('Synced correct files from active directory to new staging directory:%s- From: %s%s- To:   %s', PHP_EOL, $activeDir, PHP_EOL, $stagingDir));

        // Stage: Execute a Composer command (that doesn't make any HTTP requests).
        $newComposerName = 'new/name';
        $this->stager->stage([
            'config',
            'name',
            $newComposerName,
        ], $activeDirPath, $stagingDirPath);

        self::assertComposerJsonName($stagingDir, $newComposerName, 'Correctly executed Composer command.');

        // Change files.
        self::changeFile($activeDir, 'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt');
        self::changeFile($stagingDir, 'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt');
        self::changeFile($stagingDir, 'CHANGE_in_staging_dir_before_syncing_back_to_active_dir.txt');

        // Delete files.
        self::deleteFile($stagingDir, 'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt');
        self::deleteFile($activeDir, 'another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt');

        // Create files.
        self::createFile($stagingDir, 'EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt');
        self::createFile($stagingDir, 'CREATE_in_staging_dir.txt');
        self::createFile($stagingDir, 'another_subdir/CREATE_in_staging_dir.txt');

        // Create symlink.
        self::createSymlink($stagingDirPath->resolved(), 'EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt', $arbitrarySymlinkTarget);

        // Sanity check to ensure that the expected changes were made.
        $deletion = array_search('DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt', $expectedStagingDirListing, true);
        unset($expectedStagingDirListing[$deletion]);
        self::assertDirectoryListing($stagingDirPath->resolved(), array_merge($expectedStagingDirListing, [
            // Additions.
            'CREATE_in_staging_dir.txt',
            'EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt',
            'another_subdir/CREATE_in_staging_dir.txt',
            'EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt',
        ]), '', sprintf('Made expected changes to the staging directory at %s', $stagingDir));

        $previousStagingDirContents = self::getDirectoryContents($stagingDir);

        // Confirm that the committer fails with unsupported symlinks present in the codebase.
        try {
            // Invoke the committer without exclusions to cause it to find symlinks in the active directory.
            $this->beginner->begin($activeDirPath, $stagingDirPath);
            $preconditionMet = true;
        } catch (PreconditionException $e) {
            $failedPrecondition = $e->getPrecondition();
            $preconditionMet = false;
            self::assertEquals(NoAbsoluteSymlinksExist::class, $failedPrecondition::class, 'Correct "codebase contains symlinks" unmet.');
        } finally {
            assert(filetype($activeDirPath->resolved() . '/EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt') === 'link', 'An actual symlink is present in the codebase.');
            self::assertFalse($preconditionMet, 'Beginner fails with symlinks present in the codebase.');
        }

        // Commit: Sync files from the staging directory back to the directory.
        $this->committer->commit($stagingDirPath, $activeDirPath, $exclusions);

        self::assertDirectoryListing($activeDirPath->resolved(), [
            'composer.json',
            // Unchanged files are left alone.
            'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt',
            'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt',
            'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt',
            'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt',
            'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
            // Files excluded by exact pathname are still in the active directory.
            'EXCLUDED_file_in_active_dir_root.txt',
            'EXCLUDED_symlink_in_active_dir_root.txt',
            'arbitrary_subdir/EXCLUDED_file.txt',
            // Files excluded by directory are still in the active directory.
            'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            'EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt',
            'another_EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            'arbitrary_subdir/with/nested/EXCLUDED_dir/with/a/file/in/it/that/is/NEVER/CHANGED/anywhere.txt',
            // Files excluded by "hidden" directory are still in the active directory.
            '.hidden_EXCLUDED_dir/one.txt',
            '.hidden_EXCLUDED_dir/two.txt',
            '.hidden_EXCLUDED_dir/three.txt',
            // Files created in the staging directory are copied back to the active directory.
            'CREATE_in_staging_dir.txt',
            'another_subdir/CREATE_in_staging_dir.txt',
            // Files changed in the staging directory are synced back. (File contents asserted below.)
            'CHANGE_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            // Files created in the staging directory in an excluded directory are NOT synced back.
            // - EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt
            // - EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt
            // Files deleted from either side are absent from the active directory.
            // - another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt
            // - DELETE_from_staging_dir_before_syncing_back_to_active_dir
        ], $stagingDirPath->resolved(), sprintf('Synced correct files from staging directory back to active directory:%s%s ->%s%s"', PHP_EOL, $stagingDir, PHP_EOL, $activeDir));

        // Unchanged file contents.
        self::assertFileNotChanged($activeDir, 'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt');
        self::assertFileNotChanged($activeDir, 'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt');
        self::assertFileNotChanged($activeDir, 'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt');
        self::assertFileNotChanged($activeDir, 'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt');
        self::assertFileNotChanged($activeDir, 'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt');
        self::assertFileNotChanged($activeDir, 'EXCLUDED_file_in_active_dir_root.txt');
        self::assertFileNotChanged($activeDir, 'arbitrary_subdir/EXCLUDED_file.txt');
        self::assertFileNotChanged($activeDir, 'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt');
        self::assertFileNotChanged($activeDir, 'another_EXCLUDED_dir/make_NO_CHANGES_anywhere.txt');
        self::assertFileNotChanged($activeDir, 'arbitrary_subdir/with/nested/EXCLUDED_dir/with/a/file/in/it/that/is/NEVER/CHANGED/anywhere.txt');
        self::assertFileNotChanged($activeDir, 'CREATE_in_staging_dir.txt');
        self::assertFileNotChanged($activeDir, 'another_subdir/CREATE_in_staging_dir.txt');

        // Changed file contents.
        self::assertComposerJsonName($activeDir, $newComposerName, 'Preserved changes to composer.json.');
        self::assertFileChanged($activeDir, 'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt', 'Preserved in the active directory changes made to an excluded file in the active directory.');
        self::assertFileChanged($activeDir, 'CHANGE_in_staging_dir_before_syncing_back_to_active_dir.txt', 'Preserved in the active directory changes made to a file in the staging directory.');
        self::assertFileChanged($activeDir, 'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');

        $currentStagingDirContents = self::getDirectoryContents($stagingDir);
        self::assertEquals(
            $previousStagingDirContents,
            $currentStagingDirContents,
            sprintf('Staging directory was not changed when syncing back to active directory:%s%s ->%s%s', PHP_EOL, $stagingDir, PHP_EOL, $activeDir),
        );

        // Clean: Remove the staging directory.
        $this->cleaner->clean($activeDirPath, $stagingDirPath);

        self::assertFileDoesNotExist($stagingDirPath->resolved(), 'Staging directory was completely removed.');
    }

    public function providerDirectories(): array
    {
        return [
            // Siblings cases.
            'Siblings: simple' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'staging-dir',
            ],
            'Siblings: trailing slash on active only' => [
                'activeDir' => 'active-dir/',
                'stagingDir' => 'staging-dir',
            ],
            'Siblings: trailing slash on staging only' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'staging-dir/',
            ],
            'Siblings: trailing slash on both' => [
                'activeDir' => 'active-dir/',
                'stagingDir' => 'staging-dir/',
            ],
            'Siblings: complex relative paths' => [
                'activeDir' => 'active-dir/../active-dir/../active-dir',
                'stagingDir' => 'staging-dir/../staging-dir/../staging-dir',
            ],
            'Siblings: absolute paths' => [
                'activeDir' => self::TEST_WORKING_DIR . '/active-dir',
                'stagingDir' => self::TEST_WORKING_DIR . '/staging-dir',
            ],
            'Siblings: one absolute path, one relative' => [
                'activeDir' => self::TEST_WORKING_DIR . '/active-dir',
                'stagingDir' => 'staging-dir',
            ],
            'Siblings: one relative path, one absolute' => [
                'activeDir' => 'active-dir',
                'stagingDir' => self::TEST_WORKING_DIR . '/staging-dir',
            ],
            'Siblings: active as CWD with trailing slash' => [
                'activeDir' => './',
                'stagingDir' => '../staging-dir',
            ],
            'Siblings: active as "dot" (.)' => [
                'activeDir' => '.',
                'stagingDir' => '../staging-dir',
            ],

            // Nested cases.
            'Nested: simple' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'active-dir/staging-dir',
            ],
            'Nested: with directory depth' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'active-dir/some/directory/depth/staging-dir',
            ],
            'Nested: absolute paths' => [
                'activeDir' => self::TEST_WORKING_DIR . '/active-dir',
                'stagingDir' => self::TEST_WORKING_DIR . '/active-dir/staging-dir',
            ],

            // These scenarios are the most important for shared hosting
            // situations, which may not provide access to paths outside the
            // codebase, e.g., the web root.
            'Nested: both dirs relative, staging as "hidden" dir' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'active-dir/.composer_staging',
            ],
            'Nested: both dirs absolute, staging as "hidden" dir' => [
                'activeDir' => self::TEST_WORKING_DIR . '/active-dir',
                'stagingDir' => self::TEST_WORKING_DIR . '/active-dir/.composer_staging',
            ],

            // Other cases.
            'Other: Staging dir in temp directory' => [
                'activeDir' => 'active-dir',
                'stagingDir' => sprintf(
                    '%s/composer-stager/%s',
                    sys_get_temp_dir(),
                    uniqid('', true),
                ),
            ],
        ];
    }

    private static function assertComposerJsonName($directory, $expected, $message = ''): void
    {
        $json = file_get_contents($directory . '/composer.json');
        assert(is_string($json));
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($data));
        assert(array_key_exists('name', $data));
        self::assertEquals($expected, $data['name'], $message);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function putJson($filename, $json): void
    {
        file_put_contents($filename, json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
