<?php

namespace Goksagun\RedisOrmBundle\ORM\Persister;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\SkeletonMapper\ObjectManagerInterface;
use Doctrine\SkeletonMapper\Persister\BasicObjectPersister;
use Doctrine\SkeletonMapper\UnitOfWork\ChangeSet;
use Predis\Client;

class RedisObjectPersister extends BasicObjectPersister
{
    /** @var ArrayCollection */
    protected $objects;

    /** @var Client $client */
    private $client;

    public function __construct(ObjectManagerInterface $objectManager, ArrayCollection $objects, string $className)
    {
        parent::__construct($objectManager, $className);

        // inject some other dependencies to the class
        $this->objects = $objects;
        $this->client = new Client();
    }

    /**
     * @param object $object
     *
     * @return mixed[]
     */
    public function persistObject($object): array
    {
        $data = $this->preparePersistChangeSet($object);

        $class = $this->getClassMetadata();

        $keyspace = strtolower($class->getName());
        $identifierValues = $class->getIdentifierValues($object);
        $field = $identifierValues['id'];

        // write the $data
        $this->client->hset($keyspace, $field, serialize($data));

        return $data;
    }

    /**
     * @param object $object
     *
     * @return mixed[]
     */
    public function updateObject($object, ChangeSet $changeSet): array
    {
        $changeSet = $this->prepareUpdateChangeSet($object, $changeSet);

        $class = $this->getClassMetadata();
        $keyspace = strtolower($class->getName());
        $identifierValues = $class->getIdentifierValues($object);
        $field = $identifierValues['id'];

        $objectData = [];

        foreach ($changeSet as $key => $value) {
            $objectData[$key] = $value;
        }

        // update the $objectData
        $this->client->hset($keyspace, $field, serialize($objectData));

        return $objectData;
    }

    /**
     * @param object $object
     */
    public function removeObject($object): void
    {
        $class = $this->getClassMetadata();
        $keyspace = strtolower($class->getName());
        $identifierValues = $class->getIdentifierValues($object);
        $field = $identifierValues['id'];

        // remove the object
        $this->client->hdel($keyspace, (array)$field);
    }
}