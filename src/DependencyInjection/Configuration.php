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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('epubli_permission');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->variableNode('microservice_name')->defaultValue('change_me_to_your_microservice_name')->end()
            ->end();

        return $treeBuilder;
    }
}