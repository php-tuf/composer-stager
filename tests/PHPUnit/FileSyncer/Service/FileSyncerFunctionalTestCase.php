<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\FileSyncer\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;
use PhpTuf\ComposerStager\Tests\TestUtils\FilesystemHelper;
use PhpTuf\ComposerStager\Tests\TestUtils\PathHelper;
use Symfony\Component\Filesystem\Path as SymfonyPath;

abstract class FileSyncerFunctionalTestCase extends TestCase
{
    private static function sourceDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute('source', PathHelper::testEnvAbsolute());
    }

    private static function sourcePath(): PathInterface
    {
        return PathFactory::create(self::sourceDirAbsolute());
    }

    public static function destinationDirAbsolute(): string
    {
        return SymfonyPath::makeAbsolute('destination', PathHelper::testEnvAbsolute());
    }

    private static function destinationPath(): PathInterface
    {
        return PathFactory::create(self::destinationDirAbsolute());
    }

    protected function setUp(): void
    {
        FilesystemHelper::createDirectories([
            self::sourceDirAbsolute(),
            self::destinationDirAbsolute(),
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

        $sut->sync(self::sourcePath(), self::destinationPath(), null, null, $givenTimeout);

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
        $link = SymfonyPath::makeAbsolute('link', self::sourceDirAbsolute());
        $target = SymfonyPath::makeAbsolute('directory', self::sourceDirAbsolute());
        FilesystemHelper::createDirectories($target);
        $file = SymfonyPath::makeAbsolute('directory/file.txt', self::sourceDirAbsolute());
        touch($file);
        symlink($target, $link);
        $sut = $this->createSut();

        $sut->sync(self::sourcePath(), self::destinationPath());

        self::assertDirectoryListing(self::destinationDirAbsolute(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }
}
