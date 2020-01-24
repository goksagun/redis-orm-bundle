<?php

namespace Goksagun\RedisOrmBundle\ORM\Persister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\SkeletonMapper\ObjectManagerInterface;
use Doctrine\SkeletonMapper\Persister\BasicObjectPersister;
use Doctrine\SkeletonMapper\UnitOfWork\ChangeSet;
use Goksagun\RedisOrmBundle\ORM\Model\Model;
use Goksagun\RedisOrmBundle\Utils\StringHelper;
use Predis\Client;

class RedisObjectPersister extends BasicObjectPersister
{
    /** @var ArrayCollection */
    protected $objects;

    /** @var Client $client */
    private $client;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Client $client,
        ArrayCollection $objects,
        string $className
    ) {
        parent::__construct($objectManager, $className);

        // inject some other dependencies to the class
        $this->client = $client;
        $this->objects = $objects;
    }

    /**
     * @param object|Model $object
     *
     * @return mixed[]
     */
    public function persistObject($object): array
    {
        $data = $this->preparePersistChangeSet($object);

        $key = $this->generateKey($object);

        // write the $data
        $data = array_map(
            function ($item) {
                if (is_array($item) && empty($item)) {
                    return null;
                }
                if (is_array($item)) {
                    return implode(',', $item);
                }

                return $item;
            },
            $data
        );
        $this->client->hmset($key, $data);
        if ($ttl = $object->getTtl()) {
            $this->client->expire($key, $ttl);
        }

        return $data;
    }

    /**
     * @param object|Model $object
     *
     * @return mixed[]
     */
    public function updateObject($object, ChangeSet $changeSet): array
    {
        $objectData = $this->prepareUpdateChangeSet($object, $changeSet);

        $key = $this->generateKey($object);

        // update the $objectData
        $objectData = array_map(
            function ($item) {
                if (is_array($item) && empty($item)) {
                    return null;
                }
                if (is_array($item)) {
                    return implode(',', $item);
                }

                return $item;
            },
            $objectData
        );
        $this->client->hmset($key, $objectData);

        return $objectData;
    }

    /**
     * @param object|Model $object
     */
    public function removeObject($object): void
    {
        $key = $this->generateKey($object);

        // remove the object
        $this->client->del((array)$key);
    }

    private function generateKey($object): string
    {
        $class = $this->getClassMetadata();
        $keyspace = StringHelper::slug($class->getName());
        $identifierValues = $class->getIdentifierValues($object);
        $id = $identifierValues['id'];
        $key = $keyspace.':'.$id;

        return $key;
    }
}