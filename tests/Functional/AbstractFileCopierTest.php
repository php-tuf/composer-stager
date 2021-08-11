<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;

abstract class AbstractFileCopierTest extends TestCase
{
    abstract protected function createSut(): FileCopierInterface;

    public function testCopy(): void
    {
        self::createFiles([
            self::ACTIVE_DIR . '/lorem/ipsum/dolor.txt',
            self::ACTIVE_DIR . '/lorem/ipsum/exclude.txt',
            self::ACTIVE_DIR . '/sit/exclude.txt',
            self::ACTIVE_DIR . '/sit/amet.txt',
            self::ACTIVE_DIR . '/adipiscing-exclude.txt',
            self::ACTIVE_DIR . '/consectetur.txt',
        ]);
        $sut = $this->createSut();

        $sut->copy(self::ACTIVE_DIR, self::STAGING_DIR, [
            'lorem/ipsum/exclude.txt',
            'sit/exclude.txt',
            'adipiscing-exclude.txt',
            realpath(self::ACTIVE_DIR . '/consectetur.txt'),
        ]);

        self::assertFileExists(self::STAGING_DIR . '/lorem/ipsum/dolor.txt');
        self::assertFileExists(self::STAGING_DIR . '/sit/amet.txt');
        self::assertFileExists(self::STAGING_DIR . '/consectetur.txt', 'Ignored unsupported absolute path exclusion.');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/lorem/ipsum/exclude.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/sit/exclude.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/adipiscing-exclude.txt');
    }

    /**
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier
     * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
     */
    public function testCopyFromDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->copy('non-existent/directory', 'lorem/ipsum');
    }
}
