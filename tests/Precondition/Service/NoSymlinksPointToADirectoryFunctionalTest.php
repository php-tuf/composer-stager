<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\FileSyncer\Factory\PhpFileSyncerFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerTestHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemTestHelper;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory
 *
 * @covers ::__construct
 * @covers ::exitEarly
 */
final class NoSymlinksPointToADirectoryFunctionalTest extends LinkPreconditionsFunctionalTestCase
{
    protected function createSut(): NoSymlinksPointToADirectory
    {
        $container = ContainerTestHelper::container();

        // Override the FileSyncerFactory implementation to always return a PhpFileSyncer.
        $fileSyncerFactory = $container->getDefinition(FileSyncerFactory::class);
        $fileSyncerFactory->setClass(PhpFileSyncerFactory::class);
        $container->setDefinition(FileSyncerFactoryInterface::class, $fileSyncerFactory);

        // Compile the container.
        $container->compile();

        return $container->get(NoSymlinksPointToADirectory::class);
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     */
    public function testFulfilledWithValidLink(): void
    {
        $parentDir = self::activeDirAbsolute();
        $link = self::makeAbsolute('link.txt', $parentDir);
        $target = self::makeAbsolute('target.txt', $parentDir);
        FilesystemTestHelper::ensureParentDirectory($link);
        FilesystemTestHelper::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath());

        self::assertTrue($isFulfilled, 'Allowed link pointing within the codebase.');
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerUnfulfilled
     */
    public function testUnfulfilled(string $targetDir, string $linkDir, string $linkDirName): void
    {
        $target = self::makeAbsolute('target_directory', $targetDir);
        $link = self::makeAbsolute('directory_link', $linkDir);
        FilesystemTestHelper::mkdir($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains symlinks that point to a directory, which is not supported. The first one is %s.',
            $linkDirName,
            $linkDir,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(self::activeDirPath(), self::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => self::testFreshFixturesDirAbsolute(),
                'linkDir' => self::activeDirAbsolute(),
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => self::testFreshFixturesDirAbsolute(),
                'linkDir' => self::stagingDirAbsolute(),
                'linkDirName' => 'staging',
            ],
        ];
    }

    /**
     * @covers ::assertIsSupportedFile
     * @covers ::isFulfilled
     *
     * @dataProvider providerExclusions
     */
    public function testFulfilledExclusions(array $links, array $exclusions, bool $shouldBeFulfilled): void
    {
        $targetDirRelative = 'target_directory';
        $targetDirAbsolute = self::makeAbsolute($targetDirRelative, self::activeDirAbsolute());
        FilesystemTestHelper::mkdir($targetDirAbsolute);
        $links = array_fill_keys($links, $targetDirRelative);
        $exclusions = self::createPathList(...$exclusions);
        FilesystemTestHelper::createSymlinks(self::activeDirAbsolute(), $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(self::activeDirPath(), self::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
