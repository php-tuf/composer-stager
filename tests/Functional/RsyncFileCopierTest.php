<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Exception\DirectoryNotFoundException;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier
 * @covers ::__construct
 * @covers ::copy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ExecutableFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\ProcessFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\Runner\AbstractRunner
 */
class RsyncFileCopierTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        self::createTestEnvironment();
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::isRsyncAvailable()) {
            return;
        }
        self::removeTestEnvironment();
    }

    protected function setUp(): void
    {
        if (!self::isRsyncAvailable()) {
            $this->markTestSkipped('Rsync is not available for testing.');
        }
    }

    protected static function isRsyncAvailable(): bool
    {
        $finder = new ExecutableFinder();
        return !($finder->find('rsync') === null);
    }

    private function createSut(): RsyncFileCopier
    {
        $container = self::getContainer();

        /** @var RsyncFileCopier $sut */
        $sut = $container->get(RsyncFileCopier::class);
        return $sut;
    }

    public function testCopy(): void
    {
        touch(self::ACTIVE_DIR . '/lorem.txt');
        touch(self::ACTIVE_DIR . '/ipsum.txt');
        touch(self::ACTIVE_DIR . '/dolor-exclude.txt');
        mkdir(self::ACTIVE_DIR . '/sit-exclude');
        $sut = $this->createSut();

        $sut->copy(self::ACTIVE_DIR, self::STAGING_DIR, [
            'dolor-exclude.txt',
            'sit-exclude',
        ]);

        self::assertFileExists(self::ACTIVE_DIR . '/lorem.txt');
        self::assertFileExists(self::ACTIVE_DIR . '/ipsum.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/dolor-exclude.txt');
        self::assertFileDoesNotExist(self::STAGING_DIR . '/sit-exclude');
    }

    /**
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\RsyncFileCopier
     */
    public function testCopyFromDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        $sut = $this->createSut();

        $sut->copy('non-existent/directory', 'lorem/ipsum');
    }
}
