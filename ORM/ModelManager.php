<?php

namespace Goksagun\RedisOrmBundle\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\SkeletonMapper\Hydrator\BasicObjectHydrator;
use Doctrine\SkeletonMapper\Mapping\ClassMetadata;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataFactory;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataInstantiator;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataInterface;
use Doctrine\SkeletonMapper\ObjectFactory;
use Doctrine\SkeletonMapper\ObjectIdentityMap;
use Doctrine\SkeletonMapper\ObjectManager;
use Doctrine\SkeletonMapper\ObjectRepository\BasicObjectRepository;
use Doctrine\SkeletonMapper\ObjectRepository\ObjectRepositoryFactory;
use Doctrine\SkeletonMapper\Persister\ObjectPersisterFactory;
use Doctrine\SkeletonMapper\UnitOfWork;
use Goksagun\RedisOrmBundle\ORM\Model\Model;
use Goksagun\RedisOrmBundle\ORM\Persister\RedisObjectPersister;
use Goksagun\RedisOrmBundle\ORM\Repository\RedisObjectDataRepository;
use Goksagun\RedisOrmBundle\Utils\FileHelper;
use Predis\Client;
use Symfony\Component\Finder\Finder;

class ModelManager implements ModelManagerInterface
{
    private $objectManager;

    public function __construct(array $config = [])
    {
        $eventManager = new EventManager();
        $classMetadataFactory = new ClassMetadataFactory(new ClassMetadataInstantiator());
        $objectFactory = new ObjectFactory();
        $objectRepositoryFactory = new ObjectRepositoryFactory();
        $objectPersisterFactory = new ObjectPersisterFactory();
        $objectIdentityMap = new ObjectIdentityMap($objectRepositoryFactory);

        $configuration = new Configuration(
            $config['host'] ?? '127.0.0.1:6379',
            $config['paths'],
            $config['options'] ?? []
        );
        $objectManager = new ObjectManager(
            $objectRepositoryFactory,
            $objectPersisterFactory,
            $objectIdentityMap,
            $classMetadataFactory,
            $eventManager
        );

        $models = (new Finder())->files()->in(
            $configuration->getPaths()
        ); //"paths" => "/Users/burak/Code/Php/symfony_libs/src/Model"
        foreach ($models->getIterator() as $fileInfo) {
            $className = FileHelper::getClassFromFile($fileInfo->getRealPath());

            $reflectionClass = new \ReflectionClass($className);

            $classMetadata = new ClassMetadata($className);
            $classMetadata->setIdentifier(['id']);
            $classMetadata->setIdentifierFieldNames(['id']);

            foreach ($reflectionClass->getProperties() as $property) {
                $propertyName = $property->getName();

                if (in_array($propertyName, Model::EXCLUDE_FROM_MAPPING)) {
                    continue;
                }

                $classMetadata->mapField(['fieldName' => $propertyName]);
            }

            $classMetadataFactory->setMetadataFor($className, $classMetadata);

            // TODO: make a factory creator for client
            $client = new Client((string)$configuration->getHosts(), $configuration->getOptions());
            $dataRepository = new RedisObjectDataRepository($objectManager, $client, new ArrayCollection(), $className);
            $persister = new RedisObjectPersister($objectManager, $client, new ArrayCollection(), $className);

            $hydrator = new BasicObjectHydrator($objectManager);
            $repository = new BasicObjectRepository(
                $objectManager,
                $dataRepository,
                $objectFactory,
                $hydrator,
                $eventManager,
                $className
            );

            $objectRepositoryFactory->addObjectRepository($className, $repository);
            $objectPersisterFactory->addObjectPersister($className, $persister);
        }

        $this->objectManager = $objectManager;
    }

    /**
     * @inheritDoc
     */
    public function find($className, $id)
    {
        return $this->objectManager->find($className, $id);
    }

    /**
     * @inheritDoc
     */
    public function persist($object)
    {
        $this->objectManager->persist($object);
    }

    /**
     * @inheritDoc
     */
    public function remove($object)
    {
        $this->objectManager->remove($object);
    }

    /**
     * @inheritDoc
     */
    public function merge($object)
    {
        $this->objectManager->merge($object);
    }

    /**
     * @inheritDoc
     */
    public function clear($objectName = null)
    {
        $this->objectManager->clear($objectName);
    }

    /**
     * @inheritDoc
     */
    public function detach($object)
    {
        $this->objectManager->detach($object);
    }

    /**
     * @inheritDoc
     */
    public function refresh($object)
    {
        $this->objectManager->refresh($object);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        $this->objectManager->flush();
    }

    /**
     * @inheritDoc
     */
    public function getMetadataFactory()
    {
        return $this->objectManager->getMetadataFactory();
    }

    /**
     * @inheritDoc
     */
    public function initializeObject($obj)
    {
        $this->objectManager->initializeObject($obj);
    }

    /**
     * @inheritDoc
     */
    public function contains($object)
    {
        return $this->objectManager->contains($object);
    }

    /**
     * @inheritDoc
     */
    public function update($object): void
    {
        $this->objectManager->update($object);
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateObject(string $className, array $data)
    {
        return $this->objectManager->getOrCreateObject($className, $data);
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->objectManager->getUnitOfWork();
    }

    /**
     * @inheritDoc
     */
    public function getRepository($className)
    {
        return $this->objectManager->getRepository($className);
    }

    /**
     * @inheritDoc
     */
    public function getClassMetadata($className): ClassMetadataInterface
    {
        return $this->objectManager->getClassMetadata($className);
    }
}