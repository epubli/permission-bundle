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
    public function load(array $configs, ContainerBuilder $container): void
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

        $definition = $container->getDefinition('epubli_permission.service.custom_permission_discovery');
        $definition->setArgument(0, $config['microservice_name']);

        $definition = $container->getDefinition('epubli_permission.guzzle.http.for_permission_export');
        $definition->setArgument(0, ['base_uri' => $config['permission_export_route']['base_uri']]);

        $definition = $container->getDefinition('epubli_permission.guzzle.http.for_aggregated_permissions');
        $definition->setArgument(0, ['base_uri' => $config['aggregated_permissions_route']['base_uri']]);

        $definition = $container->getDefinition('epubli_permission.service.permission_exporter');
        $definition->setArgument(1, $config['permission_export_route']['path']);
        $definition->setArgument(2, $config['permission_export_route']['permission']);

        $definition = $container->getDefinition('epubli_permission.service.access_token');
        $definition->setArgument(1, $config['aggregated_permissions_route']['path']);
        $definition->setArgument(2, $config['aggregated_permissions_route']['permission']);
        $definition->setArgument(3, $config['is_test_environment']);
    }
}