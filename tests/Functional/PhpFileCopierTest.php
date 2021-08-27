<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopier;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopier
 * @covers ::__construct
 * @covers ::copy
 * @uses \PhpTuf\ComposerStager\Infrastructure\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\PhpFileCopier
 * @uses \PhpTuf\ComposerStager\Util\DirectoryUtil
 */
class PhpFileCopierTest extends AbstractFileCopierTest
{
    protected const ACTIVE_DIR = '.';
    protected const STAGING_DIR = '.composer_staging';

    public static function setUpBeforeClass(): void
    {
        self::createTestEnvironment();
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): FileCopierInterface
    {
        $container = self::getContainer();

        /** @var PhpFileCopier $sut */
        $sut = $container->get(PhpFileCopier::class);
        return $sut;
    }
}
