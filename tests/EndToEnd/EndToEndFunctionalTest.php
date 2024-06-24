<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\EndToEnd;

use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CleanerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\Internal\Core\Beginner;
use PhpTuf\ComposerStager\Internal\Core\Cleaner;
use PhpTuf\ComposerStager\Internal\Core\Committer;
use PhpTuf\ComposerStager\Internal\Core\Stager;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoAbsoluteSymlinksExist;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\EnvironmentTestHelper;

/**
 * Provides end-to-end functional tests, including the API and internal layers.
 *
 * @coversNothing
 *
 * @group slow
 */
final class EndToEndFunctionalTest extends TestCase
{
    private const CHANGED_CONTENT = 'changed';
    private const ORIGINAL_CONTENT = '';

    private BeginnerInterface $beginner;
    private CleanerInterface $cleaner;
    private CommitterInterface $committer;
    private StagerInterface $stager;

    protected function setUp(): void
    {
        $container = ContainerTestHelper::container();
        $container->compile();

        // Get services.
        $this->beginner = $container->get(Beginner::class);
        $this->stager = $container->get(Stager::class);
        $this->committer = $container->get(Committer::class);
        $this->cleaner = $container->get(Cleaner::class);

        // Create the test environment.
        self::createTestEnvironment();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    /**
     * @dataProvider providerDirectories
     *
     * @group slow
     */
    public function testSync(string $activeDir, string $stagingDir): void
    {
        $activeDirPath = self::createPath($activeDir);
        $activeDirAbsolute = $activeDirPath->absolute();
        $stagingDirPath = self::createPath($stagingDir);
        $stagingDirAbsolute = $stagingDirPath->absolute();

        // Create fixture (active directory).
        self::touch([
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
            // Files included despite including excluded paths at the wrong depth.
            'not/an/EXCLUDED_dir/file.txt',
            'not/the/arbitrary_subdir/with/an/EXCLUDED_file.txt',
            // Files to be changed in the staging directory.
            'CHANGE_CONTENT_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt',
            // Files to be deleted from the staging directory.
            'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt',
            // Excluded file to be deleted from the ACTIVE directory after syncing to the staging directory.
            'another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt',
        ], $activeDirAbsolute);
        self::chmod(self::makeAbsolute('CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt', $activeDirAbsolute), 0775);
        self::mkdir('empty_dir_in_active_dir_root_NEVER_CHANGED_anywhere', $activeDirAbsolute);
        self::mkdir('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute);
        self::mkdir('CHANGE_dir_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute);
        self::chmod(self::makeAbsolute('CHANGE_dir_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute), 0775);

        $arbitrarySupportedSymlinkTarget = 'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt';
        self::createSymlinks([
            'EXCLUDED_symlink_in_active_dir_root.txt' => $arbitrarySupportedSymlinkTarget,
            'EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt' => $arbitrarySupportedSymlinkTarget,
            'EXCLUDED_dir/UNSUPPORTED_link_pointing_outside_the_codebase.txt' => __FILE__,
        ], $activeDirAbsolute);
        self::createOrFakeSymlink('symlink_to_a_directory', 'arbitrary_subdir', $activeDirAbsolute);

        // Create initial composer.json. (Doing so manually can be up to one
        // third (1/3) faster than actually using Composer.)
        self::putJson(
            $activeDirAbsolute . '/composer.json',
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
            // Non-existent (ignore).
            'file_that_NEVER_EXISTS_anywhere.txt',
            // Absolute path (ignore).
            self::makeAbsolute('absolute/path'),
        ];
        $exclusions = self::createPathList(...$exclusions);

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
            self::assertTestSymlinkExists($activeDirAbsolute);
            self::assertFalse($preconditionMet, 'Beginner fails with symlinks present in the codebase.');
        }

        // Begin: Sync files from the active directory to the new staging directory.
        $this->beginner->begin($activeDirPath, $stagingDirPath, $exclusions);

        $expectedStagingDirListing = [
            'composer.json',
            'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt',
            'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt',
            'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt',
            'CHANGE_CONTENT_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
            'not/an/EXCLUDED_dir/file.txt',
            'not/the/arbitrary_subdir/with/an/EXCLUDED_file.txt',
            'symlink_to_a_directory',
        ];
        self::assertDirectoryListing($stagingDirPath->absolute(), $expectedStagingDirListing, '', sprintf('Synced correct files from active directory to new staging directory:%s- From: %s%s- To:   %s', PHP_EOL, $activeDir, PHP_EOL, $stagingDir));
        self::assertDirectoryExists(self::makeAbsolute('empty_dir_in_active_dir_root_NEVER_CHANGED_anywhere', $stagingDirAbsolute));
        self::assertDirectoryExists(self::makeAbsolute('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $stagingDirAbsolute));
        self::assertFileMode(self::makeAbsolute('CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt', $stagingDirAbsolute), 0775);
        self::assertFileMode(self::makeAbsolute('CHANGE_dir_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir', $stagingDirAbsolute), 0775);

        // Stage: Execute a Composer command (that doesn't make any HTTP requests).
        $newComposerName = 'new/name';
        $this->stager->stage([
            'config',
            'name',
            $newComposerName,
        ], $activeDirPath, $stagingDirPath);

        self::assertComposerJsonName($stagingDirAbsolute, $newComposerName, 'Correctly executed Composer command.');

        // Make changes in the active directory.
        self::changeFile($activeDirAbsolute, 'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt');
        self::rm(self::makeAbsolute('another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt', $activeDirAbsolute));
        self::rm(self::makeAbsolute('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute));

        // Make changes in the staging directory.
        self::changeFile($stagingDirAbsolute, 'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt');
        self::changeFile($stagingDirAbsolute, 'CHANGE_CONTENT_in_staging_dir_before_syncing_back_to_active_dir.txt');
        self::chmod(self::makeAbsolute('CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt', $stagingDirAbsolute), 0777);
        self::chmod(self::makeAbsolute('CHANGE_dir_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir', $stagingDirAbsolute), 0777);
        self::touch('EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt', $stagingDirAbsolute);
        self::touch('CREATE_in_staging_dir.txt', $stagingDirAbsolute);
        self::touch('another_subdir/CREATE_in_staging_dir.txt', $stagingDirAbsolute);
        self::rm(self::makeAbsolute('DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt', $stagingDirAbsolute));
        self::rm(self::makeAbsolute('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $stagingDirAbsolute));
        self::createOrFakeSymlink(
            'EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt',
            $arbitrarySupportedSymlinkTarget,
            $stagingDirAbsolute,
        );

        // Sanity check to ensure that the expected changes were made.
        $expectedStagingDirListing = array_filter($expectedStagingDirListing, static fn ($path): bool => $path !== 'DELETE_from_staging_dir_before_syncing_back_to_active_dir.txt');
        self::assertDirectoryListing($stagingDirAbsolute, [
            ...$expectedStagingDirListing,
            // Additions.
            'CREATE_in_staging_dir.txt',
            'EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt',
            'another_subdir/CREATE_in_staging_dir.txt',
            'EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt',
        ], '', sprintf('Made expected changes to the staging directory at %s', $stagingDir));
        self::assertFileMode(self::makeAbsolute('CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt', $stagingDirAbsolute), 0777);
        self::assertFileMode(self::makeAbsolute('CHANGE_dir_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir', $stagingDirAbsolute), 0777);
        self::assertDirectoryExists(self::makeAbsolute('empty_dir_in_active_dir_root_NEVER_CHANGED_anywhere', $stagingDirAbsolute));
        self::assertDirectoryDoesNotExist(self::makeAbsolute('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute));

        $previousStagingDirContents = self::getDirectoryContents($stagingDir);

        // Confirm that the committer fails with unsupported symlinks present in the codebase.
        try {
            // Invoke the committer without exclusions to cause it to find symlinks in the active directory.
            $this->beginner->begin($activeDirPath, $stagingDirPath);
            $preconditionMet = true;
        } catch (PreconditionException $e) {
            $failedPrecondition = $e->getPrecondition();
            self::assertEquals(NoAbsoluteSymlinksExist::class, $failedPrecondition::class, 'Correct "codebase contains symlinks" unmet.');
        } finally {
            self::assertTestSymlinkExists($activeDirAbsolute);
            self::assertFalse($preconditionMet, 'Beginner fails with symlinks present in the codebase.');
        }

        // Commit: Sync files from the staging directory back to the directory.
        $this->committer->commit($stagingDirPath, $activeDirPath, $exclusions);

        self::assertDirectoryListing($activeDirAbsolute, [
            'composer.json',
            // Unchanged files are left alone.
            'file_in_active_dir_root_NEVER_CHANGED_anywhere.txt',
            'arbitrary_subdir/file_NEVER_CHANGED_anywhere.txt',
            'somewhat/deeply/nested/file/that/is/NEVER_CHANGED_anywhere.txt',
            'very/deeply/nested/file/that/is/NEVER/CHANGED/in/either/the/active/directory/or/the/staging/directory.txt',
            'long_filename_NEVER_CHANGED_one_two_three_four_five_six_seven_eight_nine_ten_eleven_twelve_thirteen_fourteen_fifteen.txt',
            'symlink_to_a_directory',
            // Files excluded by exact pathname are still in the active directory.
            'EXCLUDED_file_in_active_dir_root.txt',
            'EXCLUDED_symlink_in_active_dir_root.txt',
            'arbitrary_subdir/EXCLUDED_file.txt',
            // Files excluded by directory are still in the active directory.
            'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'EXCLUDED_dir/make_NO_CHANGES_anywhere.txt',
            'EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt',
            'EXCLUDED_dir/UNSUPPORTED_link_pointing_outside_the_codebase.txt',
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
            'CHANGE_CONTENT_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt',
            // Files created in the staging directory in an excluded directory are NOT synced back.
            // - EXCLUDED_dir/but_create_file_in_it_in_the_staging_dir.txt
            // - EXCLUDED_dir/symlink_CREATED_in_staging_dir.txt
            // Files deleted from either side are absent from the active directory.
            // - another_EXCLUDED_dir/DELETE_file_from_active_dir_after_syncing_to_staging_dir.txt
            // - DELETE_from_staging_dir_before_syncing_back_to_active_dir
            // Files included despite including excluded paths at the wrong depth.
            'not/an/EXCLUDED_dir/file.txt',
            'not/the/arbitrary_subdir/with/an/EXCLUDED_file.txt',
        ], $stagingDirAbsolute, sprintf('Synced correct files from staging directory back to active directory:%s%s ->%s%s"', PHP_EOL, $stagingDir, PHP_EOL, $activeDir));
        self::assertFileMode(self::makeAbsolute('CHANGE_file_PERMISSIONS_in_staging_dir_before_syncing_back_to_active_dir.txt', $activeDirAbsolute), 0777);
        self::assertDirectoryExists(self::makeAbsolute('empty_dir_in_active_dir_root_NEVER_CHANGED_anywhere', $activeDirAbsolute));
        self::assertDirectoryDoesNotExist(self::makeAbsolute('empty_dir_in_active_that_is_DELETED_from_staging_dir_before_syncing_back_to_active_dir', $activeDirAbsolute));

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
        self::assertFileChanged($activeDir, 'CHANGE_CONTENT_in_staging_dir_before_syncing_back_to_active_dir.txt', 'Preserved in the active directory changes made to a file in the staging directory.');
        self::assertFileChanged($activeDir, 'EXCLUDED_dir/CHANGE_file_in_active_dir_after_syncing_to_staging_dir.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'very/deeply/nested/file/that/is/CHANGED/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');

        $currentStagingDirContents = self::getDirectoryContents($stagingDir);
        self::assertEquals(
            $previousStagingDirContents,
            $currentStagingDirContents,
            sprintf(
                'Staging directory was not changed when syncing back to active directory:%s%s ->%s%s',
                PHP_EOL,
                $stagingDirAbsolute,
                PHP_EOL,
                $activeDirAbsolute,
            ),
        );

        // Clean: Remove the staging directory.
        $this->cleaner->clean($activeDirPath, $stagingDirPath);

        self::assertFileDoesNotExist($stagingDirPath->absolute(), 'Staging directory was completely removed.');
    }

    public function providerDirectories(): array
    {
        $data = [
            'Siblings' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'staging-dir',
            ],
            'Shared distant ancestor' => [
                'activeDir' => 'one/two/three/four/five/six/seven/eight/nine/ten/active-dir',
                'stagingDir' => 'eleven/twelve/thirteen/fourteen/fifteen/staging-dir',
            ],
        ];

        // Some scenarios are unsupported on Windows:
        if (!EnvironmentTestHelper::isWindows()) {
            // @phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
            $data = [
                ...$data,
                ...[
                    // Using the temp directory as the staging directory is not currently
                    // supported by extension of the fact that having the active and
                    // staging directories on different drives is not unsupported--because
                    // the temp directory is commonly on a separate drive from the web root.
                    // @see https://github.com/php-tuf/composer-stager/issues/326
                    'Temp directory' => [
                        'activeDir' => 'active-dir',
                        'stagingDir' => sprintf(
                            '%s/composer-stager/%s',
                            sys_get_temp_dir(),
                            uniqid('', true),
                        ),
                    ],

                    // This scenario is important for shared hosting, which may not
                    // provide access to paths outside the codebase, e.g., the web root.
                    'Staging directory nested within active directory' => [
                        'activeDir' => 'active-dir',
                        'stagingDir' => 'active-dir/staging-dir',
                    ],
                ],
            ];
            // @phpcs:enable Squiz.Arrays.ArrayDeclaration.ValueNoNewline
        }

        return $data;
    }

    /**
     * Symlinks are unsupported on Windows, so fake them by creating a file
     * at the same location to at least satisfy directory listing assertions.
     */
    private static function createOrFakeSymlink(string $link, string $target, ?string $basePath = null): void
    {
        if (EnvironmentTestHelper::isWindows()) {
            self::touch($link, $basePath);

            return;
        }

        self::createSymlinks([$link => $target], $basePath);
    }

    private static function changeFile(string $dir, string $filename): void
    {
        $fileAbsolute = self::ensureTrailingSlash($dir) . $filename;
        $result = file_put_contents($fileAbsolute, self::CHANGED_CONTENT);
        assert($result !== false, sprintf('Failed to change file: %s', $fileAbsolute));
    }

    private static function putJson(string $filename, array $json): void
    {
        file_put_contents($filename, json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private static function assertComposerJsonName(string $directory, mixed $expected, string $message = ''): void
    {
        $json = file_get_contents($directory . '/composer.json');
        assert(is_string($json));
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($data));
        assert(array_key_exists('name', $data));
        self::assertEquals($expected, $data['name'], $message);
    }

    private static function assertFileChanged(string $dir, string $path, string $message = ''): void
    {
        self::assertStringEqualsFile(
            self::ensureTrailingSlash($dir) . $path,
            self::CHANGED_CONTENT,
            $message,
        );
    }

    private static function assertFileNotChanged(string $dir, string $path, string $message = ''): void
    {
        self::assertStringEqualsFile(
            self::ensureTrailingSlash($dir) . $path,
            self::ORIGINAL_CONTENT,
            $message,
        );
    }

    private static function assertTestSymlinkExists(string $activeDirAbsolute): void
    {
        $symlink = self::makeAbsolute('EXCLUDED_dir/symlink_NEVER_CHANGED_anywhere.txt', $activeDirAbsolute);
        assert(is_link($symlink), sprintf('Expected symlink is missing from the codebase: %s', $symlink));
    }
}
