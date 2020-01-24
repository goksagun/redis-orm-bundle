<?php

namespace Goksagun\RedisOrmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('redis_orm');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('paths')->defaultValue('%kernel.project_dir%/src/Model')->end()
            ->end()
            ->children()
                ->arrayNode('model_managers')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('paths')->defaultValue('%kernel.project_dir%/src/Model')->end()
                        ->end()
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1:6379')->end()
                        ->end()
                        ->children()
                            ->arrayNode('options')
                                ->children()
                                    ->scalarNode('profile')->end()
                                    ->scalarNode('prefix')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('shards')
                    ->children()
                        ->scalarNode('paths')->defaultValue('%kernel.project_dir%/src/Model')->end()
                    ->end()
                    ->children()
                        ->arrayNode('options')
                            ->children()
                                ->scalarNode('profile')->end()
                                ->scalarNode('prefix')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->children()
                        ->arrayNode('hosts')
                            ->beforeNormalization()->castToArray()->end()
                            ->defaultValue([])
                            ->info('Shard IP addresses')
                            ->scalarPrototype()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}