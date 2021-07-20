<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier::__construct
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier::copy
 */
class SymfonyFileCopierTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        self::createTestEnvironment();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    private function createSut(): SymfonyFileCopier
    {
        $filesystem = new Filesystem();
        return new SymfonyFileCopier($filesystem);
    }

    /**
     * @covers ::createIterator
     */
    public function testCopy(): void
    {
        touch(self::ACTIVE_DIR . '/lorem.txt');
        touch(self::ACTIVE_DIR . '/ipsum.txt');
        $sut = $this->createSut();

        $sut->copy(self::ACTIVE_DIR, self::STAGING_DIR, []);

        self::assertActiveAndStagingDirectoriesSame();
    }

    /**
     * @covers ::createIterator
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
     */
    public function testCopyFromDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->copy('non-existent/directory', 'lorem/ipsum');
    }
}
