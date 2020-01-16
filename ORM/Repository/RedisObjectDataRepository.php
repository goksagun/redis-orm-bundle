<?php

namespace Goksagun\RedisOrmBundle\ORM\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\SkeletonMapper\DataRepository\BasicObjectDataRepository;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataInterface;
use Doctrine\SkeletonMapper\ObjectManagerInterface;
use Predis\Client;

class RedisObjectDataRepository extends BasicObjectDataRepository
{
    /** @var ArrayCollection */
    protected $objects;

    /** @var Client $client */
    private $client;

    /** @var ClassMetadataInterface */
    protected $class;

    public function __construct(ObjectManagerInterface $objectManager, ArrayCollection $objects, string $className)
    {
        parent::__construct($objectManager, $className);

        // inject some other dependencies to the class
        $this->objects = $objects;
        $this->client = new Client();
    }

    /**
     * @return mixed[][]
     */
    public function findAll(): array
    {
        $class = $this->getClassMetadata();
        $keyspace = strtolower($class->getName());

        $objectsData = [];
        foreach ($this->client->hgetall($keyspace) as $objectData) {
            $objectsData[] = unserialize($objectData);
        }

        return $objectsData;
    }

    /**
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     *
     * @return mixed[][]
     */
    public function findBy(
        array $criteria, // [['id' => 1], ['id' => 2]]
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $class = $this->getClassMetadata();
        $keyspace = strtolower($class->getName());
        $identifierValues = $criteria;

        $keys = [];
        foreach ($identifierValues as $identifierValue) {
            $keys[] = $identifierValue['id'];
        }

        $objectsData = [];
        foreach ($this->client->hmget($keyspace, $keys) as $objectData) {
            $objectsData[] = unserialize($objectData);
        }

        return $objectsData;
    }

    /**
     * @param mixed[] $criteria
     *
     * @return null|mixed[]
     */
    public function findOneBy(array $criteria): ?array
    {
        $class = $this->getClassMetadata();
        $keyspace = strtolower($class->getName());
        $identifierValue = $criteria;

        $key = $identifierValue['id'];

        $objectData = $this->client->hget($keyspace, $key);

        if (!$objectData) {
            return null;
        }

        return unserialize($objectData);
    }

    public function getClassMetadata() : ClassMetadataInterface
    {
        if ($this->class === null) {
            $this->class = $this->objectManager->getClassMetadata($this->className);
        }

        return $this->class;
    }
}
