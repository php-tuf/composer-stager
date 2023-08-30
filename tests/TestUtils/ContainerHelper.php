<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Tests\TestUtils;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ContainerHelper
{
    public static function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());
        $config = PathHelper::makeAbsolute('config/services.yml', PathHelper::repositoryRootAbsolute());
        $loader->load($config);

        return $container;
    }
}
