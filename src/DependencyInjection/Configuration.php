<?php

namespace Epubli\PermissionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Epubli\PermissionBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    public const DEFAULT_MICROSERVICE_NAME = 'CHANGE_ME_TO_THE_NAME_OF_YOUR_MICROSERVICE';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('epubli_permission');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('microservice_name')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_MICROSERVICE_NAME)
                ->end()
                ->booleanNode('is_test_environment')
                    ->defaultFalse()
                ->end()
                ->arrayNode('permission_export_route')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_uri')
                            ->cannotBeEmpty()
                            ->defaultValue('http://user')
                        ->end()
                        ->scalarNode('path')
                            ->cannotBeEmpty()
                            ->defaultValue('/api/roles/permissions/import')
                        ->end()
                        ->scalarNode('permission')
                            ->cannotBeEmpty()
                            ->defaultValue('user.permission.create_permissions')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('aggregated_permissions_route')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_uri')
                            ->cannotBeEmpty()
                            ->defaultValue('http://user')
                        ->end()
                        ->scalarNode('path')
                            ->cannotBeEmpty()
                            ->defaultValue('/api/roles/{role_id}/aggregated-permissions')
                        ->end()
                        ->scalarNode('permission')
                            ->cannotBeEmpty()
                            ->defaultValue('user.role.role_get_aggregated_permissions')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('all_permissions_route')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_uri')
                            ->cannotBeEmpty()
                            ->defaultValue('http://user')
                        ->end()
                        ->scalarNode('path')
                            ->cannotBeEmpty()
                            ->defaultValue('/api/permissions?page=1')
                        ->end()
                        ->scalarNode('permission')
                            ->cannotBeEmpty()
                            ->defaultValue('user.permission.read')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}