<?php

namespace PhpTuf\ComposerStager\Tests\Functional\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\Functional\TestCase;

abstract class FileSyncerTestCase extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    abstract protected function createSut(): FileSyncerInterface;

    /**
     * @dataProvider providerDirectories
     */
    public function testSync($activeDir, $stagingDir): void
    {
        self::removeTestEnvironment();
        self::createTestEnvironment($activeDir);

        self::createFiles($activeDir, [
            'no_changes_ever.txt',
            'exclude_dir/to_test_recursion.txt',
            'exclude_dir/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'exclude_file.txt',
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'delete_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
        ]);
        $exclusions = [
            'exclude_dir',
            'exclude_file.txt',
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
        ];

        $sut = $this->createSut();
        $sut->sync($activeDir, $stagingDir, $exclusions);

        self::assertDirectoryListing($stagingDir, [
            'no_changes_ever.txt',
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            'delete_from_staging_dir_before_syncing_back_to_active_dir.txt',
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt',
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt',
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt',
        ]);

        self::changeFile($activeDir, 'exclude_dir/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt');
        self::createFile($stagingDir, 'exclude_dir/but_create_file_in_it_in_the_staging_dir.txt');
        self::changeFile($stagingDir, 'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt');
        self::changeFile($stagingDir, 'change_in_staging_dir_before_syncing_back_to_active_dir.txt');
        self::deleteFile($stagingDir, 'delete_from_staging_dir_before_syncing_back_to_active_dir.txt');
        self::createFile($stagingDir, 'create_in_staging_dir.txt');

        $sut = $this->createSut();
        $sut->sync($stagingDir, $activeDir, $exclusions);

        self::assertDirectoryListing($activeDir, [
            'no_changes_ever.txt', // Unchanged file was preserved and unsupported absolute path exclusion was ignored.
            'exclude_dir/to_test_recursion.txt', // Did not delete from the active directory an excluded directory absent from the staging directory.
            'exclude_dir/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt',
            'exclude_file.txt', // Did not delete from the active directory an excluded file absent from the staging directory.
            'change_in_staging_dir_before_syncing_back_to_active_dir.txt',
            // This file should be absent from the active directory because it was deleted from the staging directory:
            // delete_from_staging_dir_before_syncing_back_to_active_dir.txt
            'very/deeply/nested/file/that/is/never/changed/in/either/the/active/directory/or/the/staging/directory.txt', // Correctly handled deeply nested, unchanged file.
            'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', // Correctly handled deeply nested, changed file.
            'long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt', // Correctly handled long filename with weird characters.
            'exclude_long_filename_lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit_sed_do_eiusmod_tempor_incididunt_ut_labore_et.txt', // Correctly excluded long filename with weird characters.
            'create_in_staging_dir.txt', // Copied back to the active directory a new file that was created new in the staging directory.
        ], $stagingDir);

        self::assertFileNotChanged($activeDir, 'no_changes_ever.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'very/deeply/nested/file/that/is/changed/in/the/staging/directory/before/syncing/back/to/the/active/directory.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'exclude_dir/and_change_file_in_active_dir_after_syncing_to_staging_dir.txt', 'Preserved a preexisting file in the active directory that was never changed anywhere.');
        self::assertFileChanged($activeDir, 'change_in_staging_dir_before_syncing_back_to_active_dir.txt', 'Preserved in the active directory changes made to a file in the staging directory.');
    }

    public function providerDirectories(): array
    {
        return [
            // Siblings without trailing slashes.
            [
                'activeDir' => 'active-dir',
                'stagingDir' => 'staging-dir',
            ],
            // Trailing slash on active directory only.
            [
                'activeDir' => 'active-dir/',
                'stagingDir' => 'staging-dir',
            ],
            // Trailing slash on staging directory only.
            [
                'activeDir' => 'active-dir',
                'stagingDir' => 'staging-dir/',
            ],
            // Trailing slash on both.
            [
                'activeDir' => 'active-dir/',
                'stagingDir' => 'staging-dir/',
            ],
            // Siblings with some directory depth.
            [
                'activeDir' => 'active-dir/lorem/ipsum/dolor/sit/amet',
                'stagingDir' => 'staging-dir/lorem/ipsum/dolor/sit/amet',
            ],
            // Complex relative paths.
            [
                'activeDir' => 'active-dir/../active-dir/../active-dir',
                'stagingDir' => 'staging-dir/../staging-dir/../staging-dir',
            ],

            // @todo RsyncFileSyncer fails these scenarios.
            //// Staging directory under active directory.
            //[
            //    'activeDir' => 'active-dir',
            //    'stagingDir' => 'active-dir/staging-dir',
            //],
            //// Staging directory under active directory with some directory depth.
            //[
            //    'activeDir' => 'active-dir',
            //    'stagingDir' => 'active-dir/some/directory/depth/staging-dir',
            //],
            //// Current working directory without a slash as staging directory
            //// slash with staging directory inside it.
            //[
            //    'activeDir' => './',
            //    'stagingDir' => './.composer_staging',
            //],

            // Current working directory without a slash as active directory
            // with staging directory inside it.
            [
                'activeDir' => '.',
                'stagingDir' => '.composer_staging',
            ],

            // @todo PhpFileSyncer fails these scenarios.
            //// Absolute path.
            //[
            //    'activeDir' => __DIR__ . '/../../../var/phpunit/test-env/active-dir',
            //    'stagingDir' => __DIR__ . '/../../../var/phpunit/test-env/staging-dir',
            //],
            //// Temp directory.
            //[
            //    'activeDir' => sprintf('%s/composer-stager/%s/active-dir', sys_get_temp_dir(), md5(time())),
            //    'stagingDir' => sprintf('%s/composer-stager/%s/staging-dir', sys_get_temp_dir(), md5(time())),
            //],
        ];
    }
}
