<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\Infrastructure\Service\Filesystem;

use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem;
use PhpTuf\ComposerStager\Tests\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * @coversNothing
 *
 * @property \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
final class FilesystemFunctionalTest extends TestCase
{
    private const SOURCE_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'source';
    private const DESTINATION_DIR = self::TEST_ENV . DIRECTORY_SEPARATOR . 'destination';

    protected function setUp(): void
    {
        // Build the service container.
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $loader->load(__DIR__ . '/../../../../config/services.yml');
        $container->compile();
        $this->container = $container;

        $filesystem = new SymfonyFilesystem();

        $filesystem->mkdir(self::SOURCE_DIR);
        $filesystem->mkdir(self::DESTINATION_DIR);
    }

    protected function tearDown(): void
    {
        self::removeTestEnvironment();
    }

    protected function createSut(): Filesystem
    {
        /** @var \PhpTuf\ComposerStager\Infrastructure\Service\Filesystem\Filesystem $filesystem */
        $filesystem = $this->container->get(Filesystem::class);

        return $filesystem;
    }

    public function testCopy(): void
    {
        $filename = 'file.txt';
        self::createFile(self::SOURCE_DIR, $filename);

        $filesystem = $this->createSut();

        $source = PathFactory::create(self::SOURCE_DIR . DIRECTORY_SEPARATOR . $filename);
        $destination = PathFactory::create(self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $filename);

        // Copy an individual file.
        $filesystem->copy($source, $destination);

        self::assertDirectoryListing(self::DESTINATION_DIR, [$filename]);
    }

    /**
     * Our filesystem service currently depends on the Symfony Filesystem component.
     * and currently delegates its copy() method directly to it. Therefore, it is
     * precisely equivalent to its implementation. Symfony's copy() documentation
     * does not specify whether it support directories as well as files. This
     * test is to discover whether it does. (At this time it does not.)
     */
    public function testSymfonyCopyDirectory(): void
    {
        $this->expectException(SymfonyIOException::class);

        $dirname = 'directory';
        $files = [
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file2',
            $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file3',
        ];
        self::createFiles(self::SOURCE_DIR, $files);
        $symfonyFilesystem = new SymfonyFilesystem();

        self::assertDirectoryListing(self::SOURCE_DIR, $files);

        // Single file copy: this should work.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname . DIRECTORY_SEPARATOR . 'arbitrary_file1',
        );

        // Directory copy: this should fail.
        $symfonyFilesystem->copy(
            self::SOURCE_DIR . DIRECTORY_SEPARATOR . $dirname,
            self::DESTINATION_DIR . DIRECTORY_SEPARATOR . $dirname,
        );
    }
}
