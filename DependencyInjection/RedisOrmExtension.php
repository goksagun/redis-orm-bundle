<?php

namespace Goksagun\RedisOrmBundle\DependencyInjection;

use Goksagun\RedisOrmBundle\ORM\ModelManager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RedisOrmExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $modelMangerDefinition = $container->getDefinition('redis_orm.model_manager');
        $modelMangerDefinition->setArgument('$config', $config['model_managers']['default'] ?? ['paths' => $config['paths']]);

        $shardManagerDefinition = $container->getDefinition('redis_orm.shard_manager');
        $shardManagerDefinition->setArgument('$config', $config['shards']);

        if (!$container->has('redis_orm.default.model_manager')) {
            $container
                ->register('redis_orm.default.model_manager', ModelManager::class)
                ->addArgument('$config', ['paths' => $config['paths']])
                ->setPublic(true)
            ;
        }

        foreach ($config['model_managers'] as $name => $argument) {
            $container
                ->register("redis_orm.{$name}.model_manager", ModelManager::class)
                ->setArgument('$config', $argument)
                ->setPublic(true)
            ;
        }
    }
}