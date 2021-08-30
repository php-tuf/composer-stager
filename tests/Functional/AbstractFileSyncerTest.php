<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\FileSyncer\FileSyncerInterface;

abstract class AbstractFileSyncerTest extends TestCase
{
    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    abstract protected function createSut(): FileSyncerInterface;

    public function testCopyBegin(): void
    {
        self::createFiles([
            self::ACTIVE_DIR . '/lorem/ipsum/EXCLUDE_FILE.txt',
            self::ACTIVE_DIR . '/dolor/EXCLUDE_FILE.txt',
            self::ACTIVE_DIR . '/dolor/PRESERVE_UNCHANGED.txt',
            self::ACTIVE_DIR . '/EXCLUDE_DIR/sit.txt',
            self::ACTIVE_DIR . '/EXCLUDE_DIR/CHANGE_IN_ACTIVE_DIR.txt',
            self::ACTIVE_DIR . '/EXCLUDE_FILE.txt',
            self::ACTIVE_DIR . '/PRESERVE_UNCHANGED.txt',
            self::ACTIVE_DIR . '/CHANGE_IN_STAGING_DIR.txt',
            self::ACTIVE_DIR . '/DELETE_FROM_STAGING_DIR.txt',
        ]);
        $sut = $this->createSut();

        $sut->sync(self::ACTIVE_DIR, self::STAGING_DIR, [
            realpath(self::ACTIVE_DIR . '/PRESERVE_UNCHANGED.txt'), // Unsupported absolute path exclusion.
            'lorem/ipsum/EXCLUDE_FILE.txt',
            'dolor/EXCLUDE_FILE.txt',
            'EXCLUDE_DIR/',
            'EXCLUDE_FILE.txt',
        ]);

        // Included files.
        self::assertFileExists(
            self::STAGING_DIR . '/PRESERVE_UNCHANGED.txt',
            'Ignored unsupported absolute path exclusion.'
        );
        self::assertFileExists(self::STAGING_DIR . '/PRESERVE_UNCHANGED.txt');
        self::assertFileExists(self::STAGING_DIR . '/CHANGE_IN_STAGING_DIR.txt');
        self::assertFileExists(self::STAGING_DIR . '/DELETE_FROM_STAGING_DIR.txt');
        // Excluded files.
        self::assertFileDoesNotExist(self::STAGING_DIR . '/lorem/ipsum/EXCLUDE_FILE.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/dolor/EXCLUDE_FILE.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE_DIR/sit.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE_DIR/CHANGE_IN_ACTIVE_DIR.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE_FILE.txt');
    }

    /**
     * @depends testCopyBegin
     */
    public function testCopyCommit(): void
    {
        // Act on the active directory.
        file_put_contents(
            self::ACTIVE_DIR . '/EXCLUDE_DIR/CHANGE_IN_ACTIVE_DIR.txt',
            self::CHANGED_CONTENT
        );
        // Act on the staging directory.
        self::createFiles([self::STAGING_DIR . '/consectetur/CREATE_IN_STAGING_DIR.txt']);
        file_put_contents(
            self::STAGING_DIR . '/CHANGE_IN_STAGING_DIR.txt',
            self::CHANGED_CONTENT
        );
        unlink(self::STAGING_DIR . '/DELETE_FROM_STAGING_DIR.txt');
        $sut = $this->createSut();

        $sut->sync(self::STAGING_DIR, self::ACTIVE_DIR, [
            realpath(self::ACTIVE_DIR . '/PRESERVE_UNCHANGED.txt'), // Unsupported absolute path exclusion.
            'lorem/ipsum/EXCLUDE_FILE.txt',
            'dolor/EXCLUDE_FILE.txt',
            'EXCLUDE_DIR/',
            'EXCLUDE_FILE.txt',
        ]);

        self::assertFileExists(
            self::STAGING_DIR,
            'Did not delete the staging directory.'
        );
        self::assertStringEqualsFile(
            self::ACTIVE_DIR . '/PRESERVE_UNCHANGED.txt',
            self::ORIGINAL_CONTENT,
            'Preserved a preexisting file in the active directory that was never changed anywhere.'
        );
        self::assertFileExists(
            self::ACTIVE_DIR . '/consectetur/CREATE_IN_STAGING_DIR.txt',
            'Copied back to the active directory a new file that was created new in the staging directory.'
        );
        self::assertStringEqualsFile(
            self::ACTIVE_DIR . '/EXCLUDE_DIR/CHANGE_IN_ACTIVE_DIR.txt',
            self::CHANGED_CONTENT,
            'Preserved changes made to a file in the active directory whose parent directory was excluded.'
        );
        self::assertFileExists(
            self::ACTIVE_DIR . '/lorem/ipsum/EXCLUDE_FILE.txt',
            'Did not delete from the active directory an excluded file.'
        );
        self::assertStringEqualsFile(
            self::ACTIVE_DIR . '/CHANGE_IN_STAGING_DIR.txt',
            self::CHANGED_CONTENT,
            'Preserved in the active directory changes made to a file in the staging directory.'
        );
        self::assertFileDoesNotExist(
            self::ACTIVE_DIR . '/DELETE_FROM_STAGING_DIR.txt',
            'Deleted from the active directory a file was removed from the staging directory.'
        );
    }
}
