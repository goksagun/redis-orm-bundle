<?php

namespace Goksagun\RedisOrmBundle\ORM;

use Doctrine\Common\Collections\ArrayCollection;

interface ShardManagerInterface
{
    public function getModelManagers(): ArrayCollection;

    public function getModelManager(int $shard): ModelManagerInterface;

    public function getRandomModelManager(): ModelManagerInterface;

    public function hasModelManager(int $shard): bool;
}