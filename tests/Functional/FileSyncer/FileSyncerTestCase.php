<?php

namespace PhpTuf\ComposerStager\Tests\Functional\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Infrastructure\FileSyncer\RsyncFileSyncer;
use PhpTuf\ComposerStager\Tests\Functional\TestCase;

abstract class FileSyncerTestCase extends TestCase
{
    private const PHP_FILE_SYNCER_ON_WINDOWS = 'windows';

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): FileSyncerInterface;

    /**
     * @dataProvider providerDirectories
     */
    public function testSync($activeDir, $stagingDir, $incomplete = []): void
    {
        $sut = $this->createSut();

        // @todo Some tests are known incomplete. Complete them, obviously.
        $sutClass = get_class($sut);
        if (in_array($sutClass, $incomplete, true)) {
            $this->markTestIncomplete();
        }
        if (in_array(self::PHP_FILE_SYNCER_ON_WINDOWS, $incomplete, true) && self::isWindows()) {
            $this->markTestIncomplete();
        }

        self::removeTestEnvironment();
        self::createTestEnvironment($activeDir);

        self::createFiles($activeDir, [
            'no_changes_ever.txt',
            'arbitrary_dir/no_changes_ever.txt',
            'arbitrary_dir/exclude_file.txt',
            'exclude_dir1/and/arbitrary_file.txt',
            'exclude_dir1/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'exclude_dir2/arbitrary_file.txt',
            'exclude_file.txt',
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'delete_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
        ]);
        $exclusions = [
            'arbitrary_dir/exclude_file.txt',
            'exclude_dir1/', // Trailing slash.
            'exclude_dir2', // No trailing slash.
            'exclude_file.txt',
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
            'non_existent_file.txt',
            implode('/', [self::TEST_ENV, $activeDir, 'no_changes_ever.txt']), // Absolute path to existing file. Unsupported--should have no effect.
        ];

        $sut->sync($activeDir, $stagingDir, $exclusions);

        self::assertDirectoryListing($stagingDir, [
            'no_changes_ever.txt',
            'arbitrary_dir/no_changes_ever.txt',
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'delete_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
        ], '', sprintf('Synced correct files from active directory to new staging directory at %s.', $stagingDir));

        self::changeFile($activeDir, 'exclude_dir1/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt');
        self::createFile($stagingDir, 'exclude_dir1/but_create_file_in_it_in_the_staging_dir.txt');
        self::changeFile($stagingDir, 'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt');
        self::changeFile($stagingDir, 'change_in_staging_dir_before_syncing_back_to_active_dir.txt');
        self::deleteFile($stagingDir, 'delete_from_staging_dir_before_syncing_back_to_active_dir.txt');
        self::createFile($stagingDir, 'create_in_staging_dir.txt');

        $sut->sync($stagingDir, $activeDir, $exclusions);

        self::assertDirectoryListing($activeDir, [
            'no_changes_ever.txt', // Preserved unchanged file and ignored unsupported absolute path exclusion.
            'arbitrary_dir/no_changes_ever.txt',
            'arbitrary_dir/exclude_file.txt',
            'exclude_dir1/and/arbitrary_file.txt', // Did not delete from the active directory an excluded directory absent from the staging directory.
            'exclude_dir1/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'exclude_dir2/arbitrary_file.txt',
            'exclude_file.txt', // Did not delete from the active directory an excluded file absent from the staging directory.
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            // This file should be absent from the active directory because it was deleted from the staging directory:
            // delete_from_staging_dir_before_syncing_back_to_active_dir.txt
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt', // Correctly handled deeply nested, unchanged file.
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', // Correctly handled deeply nested, changed file.
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt', // Correctly handled long filename with weird characters.
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt', // Correctly excluded long filename with weird characters.
            'create_in_staging_dir.txt', // Copied back to the active directory a new file that was created new in the staging directory.
        ], $stagingDir, sprintf('Synced correct files from staging directory back to active directory at %s.', $activeDir));

        self::assertFileNotChanged($activeDir, 'no_changes_ever.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'exclude_dir1/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'change_in_staging_dir_before_syncing_back_to_active_dir.txt', 'Preserved in the active directory changes made to a file in the staging directory.');
        self::assertFileExists($stagingDir);

        self::assertDirectoryListing($stagingDir, [
            'no_changes_ever.txt',
            'arbitrary_dir/no_changes_ever.txt',
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
            'exclude_dir1/but_create_file_in_it_in_the_staging_dir.txt',
            'create_in_staging_dir.txt',
        ], '', sprintf('Preserved staging directory at %s after syncing it back to active directory.', $stagingDir));
    }

    public function providerDirectories(): array
    {
        $random = uniqid('', true);
        $tempDir = sprintf('%s/composer-stager/%s', sys_get_temp_dir(), $random);
        return [
            'Siblings: no trailing slashes' => [
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
                'activeDir' => self::TEST_ENV_WORKING_DIR . '/active-dir',
                'stagingDir' => self::TEST_ENV_WORKING_DIR . '/staging-dir',
            ],
            'Siblings: temp directory' => [
                'activeDir' => $tempDir . '/active-dir',
                'stagingDir' => $tempDir . '/staging-dir',
                // @todo
                'incomplete' => [RsyncFileSyncer::class],
            ],
            'Siblings: active as CWD with trailing slash' => [
                'activeDir' => './',
                'stagingDir' => '../staging-dir',
            ],
            'Siblings: active as "dot"' => [
                'activeDir' => '.',
                'stagingDir' => '../staging-dir',
                'incomplete' => [self::PHP_FILE_SYNCER_ON_WINDOWS],
            ],
            'Siblings: staging as CWD with trailing slash' => [
                'activeDir' => '../active-dir',
                'stagingDir' => './',
            ],
            'Siblings: staging as "dot"' => [
                'activeDir' => '../active-dir',
                'stagingDir' => '.',
                'incomplete' => [self::PHP_FILE_SYNCER_ON_WINDOWS],
            ],

            'Nested: simple' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'active-dir/staging-dir',
                // @todo
                'incomplete' => [
                    RsyncFileSyncer::class,
                    self::PHP_FILE_SYNCER_ON_WINDOWS,
                ],
            ],
            'Nested: with directory depth' => [
                'activeDir' => 'active-dir',
                'stagingDir' => 'active-dir/some/directory/depth/staging-dir',
                // @todo
                'incomplete' => [
                    RsyncFileSyncer::class,
                    self::PHP_FILE_SYNCER_ON_WINDOWS,
                ],
            ],
            'Nested: absolute paths' => [
                'activeDir' => self::TEST_ENV_WORKING_DIR . '/active-dir',
                'stagingDir' => self::TEST_ENV_WORKING_DIR . '/active-dir/staging-dir',
                // @todo
                'incomplete' => [
                    RsyncFileSyncer::class,
                    self::PHP_FILE_SYNCER_ON_WINDOWS,
                ],
            ],
            // This scenario is the most important for shared hosting situations,
            // which may not provide access to paths outside the application root,
            // e.g., the web root.
            'Nested: active as "dot", staging as "hidden" dir' => [
                'activeDir' => '.',
                'stagingDir' => '.composer_staging',
            ],
            'Nested: temp directory' => [
                'activeDir' => $tempDir . '/active-dir',
                'stagingDir' => $tempDir . '/active-dir/staging-dir',
                // @todo
                'incomplete' => [
                    PhpFileSyncer::class,
                    RsyncFileSyncer::class,
                ],
            ],
        ];
    }
}
