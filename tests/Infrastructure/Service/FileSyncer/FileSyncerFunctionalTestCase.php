<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @property \PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\Infrastructure\Value\Path\TestPath $source
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 */
abstract class FileSyncerFunctionalTestCase extends TestCase
{
    private const SOURCE_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'source';
    private const DESTINATION_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'destination';

    protected function setUp(): void
    {
        $this->source = new TestPath(self::SOURCE_DIR);
        $this->destination = new TestPath(self::DESTINATION_DIR);

        $this->filesystem = new SymfonyFilesystem();

        $this->filesystem->mkdir(self::TEST_WORKING_DIR);
        chdir(self::TEST_WORKING_DIR);

        $this->filesystem->mkdir($this->source->resolve());
        $this->filesystem->mkdir($this->destination->resolve());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    final protected function createSut(): FileSyncerInterface
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface $sut */
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

        $sut->sync($this->source, $this->destination, null, null, $givenTimeout);

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
        $link = PathFactory::create('link', $this->source);
        $target = PathFactory::create('directory', $this->source);
        $this->filesystem->mkdir($target->resolve());
        $file = PathFactory::create('directory/file.txt', $this->source)->resolve();
        $this->filesystem->touch($file);
        symlink($target->resolve(), $link->resolve());
        $sut = $this->createSut();

        $sut->sync($this->source, $this->destination);

        self::assertDirectoryListing($this->destination->resolve(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }
}
