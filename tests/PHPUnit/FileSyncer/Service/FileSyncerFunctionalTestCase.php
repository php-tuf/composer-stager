<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;

abstract class FileSyncerFunctionalTestCase extends TestCase
{
    protected function setUp(): void
    {
        FilesystemHelper::createDirectories([
            PathHelper::sourceDirAbsolute(),
            PathHelper::destinationDirAbsolute(),
        ]);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    final protected function createSut(): FileSyncerInterface
    {
        $container = $this->container();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface $sut */
        $sut = $container->get($this->fileSyncerClass());

        return $sut;
    }

    abstract protected function fileSyncerClass(): string;

    /**
     * @covers ::sync
     *
     * @dataProvider providerSyncTimeout
     */
    public function testSyncTimeout(?int $givenTimeout, int $expectedTimeout): void
    {
        $sut = $this->createSut();

        $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath(), null, null, $givenTimeout);

        self::assertSame((string) $expectedTimeout, ini_get('max_execution_time'), 'Correctly set process timeout.');
    }

    public function providerSyncTimeout(): array
    {
        return [
            [
                'givenTimeout' => null,
                'expectedTimeout' => 0,
            ],
            [
                'givenTimeout' => 10,
                'expectedTimeout' => 10,
            ],
        ];
    }

    /** @covers ::sync */
    public function testSyncWithDirectorySymlinks(): void
    {
        $link = PathHelper::makeAbsolute('link', PathHelper::sourceDirAbsolute());
        $target = PathHelper::makeAbsolute('directory', PathHelper::sourceDirAbsolute());
        FilesystemHelper::createDirectories($target);
        $file = PathHelper::makeAbsolute('directory/file.txt', PathHelper::sourceDirAbsolute());
        touch($file);
        symlink($target, $link);
        $sut = $this->createSut();

        $sut->sync(PathHelper::sourceDirPath(), PathHelper::destinationDirPath());

        self::assertDirectoryListing(PathHelper::destinationDirAbsolute(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }
}
