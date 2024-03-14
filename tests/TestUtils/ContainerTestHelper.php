<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ContainerTestHelper
{
    public static function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $config = PathTestHelper::makeAbsolute('config/services.yml', FixtureTestHelper::repositoryRootAbsolute());
        $loader->load($config);

        return $container;
    }

    public static function get(
        string $id,
        int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
    ): ?object {
        $container = self::container();
        $container->compile();

        return $container->get($id, $invalidBehavior);
    }
}
