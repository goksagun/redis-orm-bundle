<?php

namespace Goksagun\RedisOrmBundle\ORM;

use Doctrine\Common\Collections\ArrayCollection;

class ShardManager implements ShardManagerInterface
{
    private $modelManagers;

    public function __construct(array $config = [])
    {
        $configuration = new Configuration($config['host'] ?? '127.0.0.1:6379', $config['paths'], $config['options'] ?? []);

        $this->modelManagers = new ArrayCollection();

        foreach ((array)$configuration->getHosts() as $host) {
            $this->modelManagers[] = new ModelManager(
                ['host' => $host, 'options' => $configuration->getOptions(), 'paths' => $configuration->getPaths()]
            );
        }
    }

    public function getModelManager(int $shard): ModelManagerInterface
    {
        if (!$this->modelManagers->containsKey($shard)) {
            throw new \InvalidArgumentException(
                sprintf('The shard manager by index %s not found', $shard)
            );
        }

        return $this->modelManagers->get($shard);
    }

    public function getModelManagers(): ArrayCollection
    {
        return $this->modelManagers;
    }

    public function getRandomModelManager(): ModelManagerInterface
    {
        return $this->modelManagers->get(array_rand($this->modelManagers->getKeys()));
    }

    public function hasModelManager(int $shard): bool
    {
        return $this->modelManagers->containsKey($shard);
    }
}