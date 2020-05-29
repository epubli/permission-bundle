<?php

namespace Epubli\PermissionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class EpubliPermissionExtension
 * @package Epubli\PermissionBundle\DependencyInjection
 */
class EpubliPermissionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.xml');

        $definition = $container->getDefinition('epubli_permission.service.permission_discovery');
        $definition->setArgument(0, $config['microservice_name']);
    }
}