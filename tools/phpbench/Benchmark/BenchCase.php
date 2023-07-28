<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\PHPBench\Benchmark;

use PhpTuf\ComposerStager\PHPBench\TestUtils\FixtureHelper;
use Symfony\Component\Config\FileLocator as SymfonyFileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as SymfonyYamlFileLoader;
use Symfony\Component\Filesystem\Path as SymfonyPath;

abstract class BenchCase
{
    protected SymfonyContainerBuilder $container;

    public function __construct()
    {
        $this->container = $this->getContainer();
    }

    public static function setUpBeforeClass(): void
    {
        FixtureHelper::ensureFixtures();
    }

    /** Provides a hook for customizing the container before compilation. */
    protected function customizeContainer(SymfonyContainerBuilder $container): void
    {
        // No default behavior.
    }

    private function getContainer(): SymfonyContainerBuilder
    {
        $container = new SymfonyContainerBuilder();
        $loader = new SymfonyYamlFileLoader($container, new SymfonyFileLocator());
        $config = SymfonyPath::makeAbsolute('config/services.yml', FixtureHelper::repositoryRootAbsolute());
        $loader->load($config);
        $this->customizeContainer($container);
        $container->compile();

        return $container;
    }
}
