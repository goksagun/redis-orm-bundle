<?php

namespace Goksagun\RedisOrmBundle\ORM\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\SkeletonMapper\DataRepository\BasicObjectDataRepository;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataInterface;
use Doctrine\SkeletonMapper\ObjectManagerInterface;
use Goksagun\RedisOrmBundle\Utils\StringHelper;
use Predis\Client;

class RedisObjectDataRepository extends BasicObjectDataRepository
{
    /** @var ArrayCollection */
    protected $objects;

    /** @var Client $client */
    private $client;

    /** @var ClassMetadataInterface */
    protected $class;

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
     * @return mixed[][]
     */
    public function findAll(): array
    {
        throw new \RuntimeException(sprintf('This %s method not support', __METHOD__));
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
        $keyspace = StringHelper::slug($class->getName());
        $identifierValues = $criteria;

        $keys = [];
        foreach ($identifierValues as $identifierValue) {
            $keys[] = $this->generateKey($keyspace, $identifierValue['id']);
        }

        $objectsData = [];
        foreach ($keys as $key) {
            if ($this->objects->containsKey($key)) {
                $objectsData[] = $this->objects->get($key);

                continue;
            }

            $this->objects[$key] = $objectsData[] = $this->client->hgetall($key);
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
        $keyspace = StringHelper::slug($class->getName());
        $identifierValue = $criteria;
        $id = $identifierValue['id'];
        $key = $this->generateKey($keyspace, $id);

        if ($this->objects->containsKey($key)) {
            $objectData = $this->objects->get($key);
        } else {
            $this->objects[$key] = $objectData = $this->client->hgetall($key);
        }

        if (!$objectData) {
            return null;
        }

        return $objectData;
    }

    public function getClassMetadata(): ClassMetadataInterface
    {
        if ($this->class === null) {
            $this->class = $this->objectManager->getClassMetadata($this->className);
        }

        return $this->class;
    }

    private function generateKey($keyspace, $identifier): string
    {
        return $keyspace.':'.$identifier;
    }
}
