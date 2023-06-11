<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Domain\Service\FileSyncer\FileSyncerInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Tests\TestCase;

/**
 * @uses \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Host\Host
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Service\Translator
 * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
 *
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $destination
 * @property \PhpTuf\ComposerStager\Domain\Value\Path\PathInterface $source
 */
abstract class FileSyncerFunctionalTestCase extends TestCase
{
    private const SOURCE_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'source';
    private const DESTINATION_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'destination';

    protected function setUp(): void
    {
        $this->source = PathFactory::create(self::SOURCE_DIR);
        $this->destination = PathFactory::create(self::DESTINATION_DIR);

        mkdir($this->source->resolved(), 0777, true);
        mkdir($this->destination->resolved(), 0777, true);
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
     * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
     * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
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

    /**
     * @covers ::sync
     *
     * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Factory\TranslatableFactory
     * @uses \PhpTuf\ComposerStager\Infrastructure\Translation\Value\TranslatableMessage
     */
    public function testSyncWithDirectorySymlinks(): void
    {
        $link = PathFactory::create('link', $this->source);
        $target = PathFactory::create('directory', $this->source);
        mkdir($target->resolved(), 0777, true);
        $file = PathFactory::create('directory/file.txt', $this->source)->resolved();
        touch($file);
        symlink($target->resolved(), $link->resolved());
        $sut = $this->createSut();

        $sut->sync($this->source, $this->destination);

        self::assertDirectoryListing($this->destination->resolved(), [
            'link',
            'directory/file.txt',
        ], '', 'Correctly synced files, including a symlink to a directory.');
    }
}
