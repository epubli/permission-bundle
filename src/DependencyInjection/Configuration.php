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
    public const DEFAULT_MICROSERVICE_NAME = 'change_me_to_your_microservice_name';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('epubli_permission');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->variableNode('microservice_name')->defaultValue(self::DEFAULT_MICROSERVICE_NAME)->end()
            ->end();

        return $treeBuilder;
    }
}