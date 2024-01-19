<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Precondition\Service;

use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\FileSyncer\Factory\FileSyncerFactoryInterface;
use PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactory;
use PhpTuf\ComposerStager\Internal\Path\Value\PathList;
use PhpTuf\ComposerStager\Internal\Precondition\Service\NoSymlinksPointToADirectory;
use PhpTuf\ComposerStager\Tests\FileSyncer\Factory\PhpFileSyncerFactory;
use PhpTuf\ComposerStager\Tests\TestUtils\ContainerHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

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
        $container = ContainerHelper::container();

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
        $parentDir = PathHelper::activeDirAbsolute();
        $link = PathHelper::makeAbsolute('link.txt', $parentDir);
        $target = PathHelper::makeAbsolute('target.txt', $parentDir);
        FilesystemHelper::ensureParentDirectory($link);
        FilesystemHelper::touch($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());

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
        $target = PathHelper::makeAbsolute('target_directory', $targetDir);
        $link = PathHelper::makeAbsolute('directory_link', $linkDir);
        FilesystemHelper::createDirectories($target);
        symlink($target, $link);
        $sut = $this->createSut();

        $message = sprintf(
            'The %s directory at %s contains symlinks that point to a directory, which is not supported. The first one is %s.',
            $linkDirName,
            $linkDir,
            $link,
        );
        self::assertTranslatableException(static function () use ($sut): void {
            $sut->assertIsFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath());
        }, PreconditionException::class, $message);
    }

    public function providerUnfulfilled(): array
    {
        return [
            'In active directory' => [
                'targetDir' => PathHelper::testFreshFixturesDirAbsolute(),
                'linkDir' => PathHelper::activeDirAbsolute(),
                'linkDirName' => 'active',
            ],
            'In staging directory' => [
                'targetDir' => PathHelper::testFreshFixturesDirAbsolute(),
                'linkDir' => PathHelper::stagingDirAbsolute(),
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
        $targetDirAbsolute = PathHelper::makeAbsolute($targetDirRelative, PathHelper::activeDirAbsolute());
        FilesystemHelper::createDirectories($targetDirAbsolute);
        $links = array_fill_keys($links, $targetDirRelative);
        $exclusions = new PathList(...$exclusions);
        FilesystemHelper::createSymlinks(PathHelper::activeDirAbsolute(), $links);
        $sut = $this->createSut();

        $isFulfilled = $sut->isFulfilled(PathHelper::activeDirPath(), PathHelper::stagingDirPath(), $exclusions);

        self::assertEquals($shouldBeFulfilled, $isFulfilled, 'Respected exclusions.');
    }
}
