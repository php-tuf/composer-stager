<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Service\FileSyncer;

use PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer;
use PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath;
use PhpTuf\ComposerStager\Tests\PHPUnit\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversDefaultClass \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 *
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem
 * @uses \PhpTuf\ComposerStager\Infrastructure\Service\Finder\RecursiveFileFinder
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\AbstractPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\UnixLikePath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\Path\WindowsPath
 * @uses \PhpTuf\ComposerStager\Infrastructure\Value\PathList\PathList
 *
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $destination
 * @property \PhpTuf\ComposerStager\Tests\PHPUnit\Infrastructure\Value\Path\TestPath $source
 */
final class PhpFileSyncerFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        $this->source = new TestPath(self::ACTIVE_DIR);
        $this->destination = new TestPath(self::STAGING_DIR);

        $filesystem = new SymfonyFilesystem();

        $filesystem->mkdir(self::TEST_WORKING_DIR);
        chdir(self::TEST_WORKING_DIR);

        $filesystem->mkdir($this->source->resolve());
        $filesystem->mkdir($this->destination->resolve());
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): PhpFileSyncer
    {
        $container = $this->getContainer();
        $container->compile();

        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\FileSyncer\PhpFileSyncer $sut */
        $sut = $container->get(PhpFileSyncer::class);

        return $sut;
    }

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
}
