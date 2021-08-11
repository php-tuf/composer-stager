<?php

namespace PhpTuf\ComposerStager\Tests\Functional;

use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\FileCopierInterface;
use PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
 * @covers ::__construct
 * @covers ::copy
 */
class SymfonyFileCopierTest extends AbstractFileCopierTest
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

        /** @var SymfonyFileCopier $sut */
        $sut = $container->get(SymfonyFileCopier::class);
        return $sut;
    }

    /**
     * @uses \PhpTuf\ComposerStager\Exception\DirectoryNotFoundException
     * @uses \PhpTuf\ComposerStager\Exception\PathException
     * @uses \PhpTuf\ComposerStager\Infrastructure\Process\FileCopier\SymfonyFileCopier
     *
     * @noinspection SenselessProxyMethodInspection
     *   This method is overridden just to add test annotations.
     */
    public function testCopyFromDirectoryNotFound(): void
    {
        parent::testCopyFromDirectoryNotFound();
    }
}
