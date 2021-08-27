<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;
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
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 */
class RsyncFileCopierTest extends AbstractFileCopierTest
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
        return $finder->find('rsync') !== null;
    }

    protected function createSut(): FileCopierInterface
    {
        $container = self::getContainer();

        /** @var RsyncFileCopier $sut */
        $sut = $container->get(RsyncFileCopier::class);
        return $sut;
    }
}
