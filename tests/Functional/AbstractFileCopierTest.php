<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;

abstract class AbstractFileCopierTest extends TestCase
{
    abstract protected function createSut(): FileCopierInterface;

    public function testCopyBegin(): void
    {
        self::createFiles([
            self::ACTIVE_DIR . '/lorem/ipsum/EXCLUDE.txt',
            self::ACTIVE_DIR . '/dolor/EXCLUDE.txt',
            self::ACTIVE_DIR . '/dolor/KEEP.txt',
            self::ACTIVE_DIR . '/EXCLUDE/sit.txt',
            self::ACTIVE_DIR . '/EXCLUDE/change.txt',
            self::ACTIVE_DIR . '/amet/CHANGE.txt',
            self::ACTIVE_DIR . '/EXCLUDE.txt',
            self::ACTIVE_DIR . '/KEEP.txt',
            self::ACTIVE_DIR . '/CHANGE.txt',
            self::ACTIVE_DIR . '/DELETE.txt',
        ]);
        $sut = $this->createSut();

        $sut->copy(self::ACTIVE_DIR, self::STAGING_DIR, [
            realpath(self::ACTIVE_DIR . '/KEEP.txt'), // Unsupported absolute path exclusion.
            'lorem/ipsum/EXCLUDE.txt',
            'dolor/EXCLUDE.txt',
            'EXCLUDE/',
            'EXCLUDE.txt',
        ]);

        // Included files.
        self::assertFileExists(self::STAGING_DIR . '/dolor/KEEP.txt');
        self::assertFileExists(self::STAGING_DIR . '/KEEP.txt');
        self::assertFileExists(self::STAGING_DIR . '/CHANGE.txt');
        self::assertFileExists(self::STAGING_DIR . '/DELETE.txt');
        self::assertFileExists(
            self::STAGING_DIR . '/KEEP.txt',
            'Ignored unsupported absolute path exclusion.'
        );
        // Excluded files.
        self::assertFileDoesNotExist(self::STAGING_DIR . '/lorem/ipsum/EXCLUDE.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/dolor/EXCLUDE.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE/sit.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE/change.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/EXCLUDE.txt');
    }

    /**
     * @depends testCopyBegin
     */
    public function testCopyCommit(): void
    {
        // Act on the active directory.
        file_put_contents(
            self::ACTIVE_DIR . '/EXCLUDE/change.txt',
            'changed'
        );
        // Act on the staging directory.
        self::createFiles([self::STAGING_DIR . '/consectetur/NEW.txt']);
        file_put_contents(
            self::STAGING_DIR . '/CHANGE.txt',
            'changed'
        );
        unlink(self::STAGING_DIR . '/DELETE.txt');
        $sut = $this->createSut();

        $sut->copy(self::STAGING_DIR, self::ACTIVE_DIR, [
            realpath(self::ACTIVE_DIR . '/KEEP.txt'), // Unsupported absolute path exclusion.
            'lorem/ipsum/EXCLUDE.txt',
            'dolor/EXCLUDE.txt',
            'EXCLUDE/',
            'EXCLUDE.txt',
        ]);

        self::assertFileExists(self::STAGING_DIR, 'Preserved staging directory.');
        self::assertFileExists(
            self::ACTIVE_DIR . '/KEEP.txt',
            'Copied new file in staging directory back to active directory'
        );
        self::assertFileExists(
            self::ACTIVE_DIR . '/consectetur/NEW.txt',
            'Copied new file in staging directory back to active directory'
        );
        self::assertEquals(
            'changed',
            file_get_contents(self::ACTIVE_DIR . '/EXCLUDE/change.txt'),
            'Preserved changes to excluded file in active directory.'
        );
        self::assertFileExists(
            self::ACTIVE_DIR . '/lorem/ipsum/EXCLUDE.txt',
            'Did not delete excluded file in active directory.'
        );
        self::assertEquals(
            'changed',
            file_get_contents(self::ACTIVE_DIR . '/CHANGE.txt'),
            'Preserved changes to excluded file in active directory.'
        );
        self::assertFileDoesNotExist(
            self::ACTIVE_DIR . '/DELETE.txt',
            'Deleted file from active directory that was removed from staging directory.'
        );
    }
}
