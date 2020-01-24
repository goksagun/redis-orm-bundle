<?php

namespace Goksagun\RedisOrmBundle\ORM\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Doctrine\SkeletonMapper\Hydrator\HydratableInterface;
use Doctrine\SkeletonMapper\Mapping\ClassMetadataInterface;
use Doctrine\SkeletonMapper\Mapping\LoadMetadataInterface;
use Doctrine\SkeletonMapper\ObjectManagerInterface;
use Doctrine\SkeletonMapper\Persister\IdentifiableInterface;
use Doctrine\SkeletonMapper\Persister\PersistableInterface;
use Doctrine\SkeletonMapper\UnitOfWork\Change;
use Doctrine\SkeletonMapper\UnitOfWork\ChangeSet;

abstract class Model implements HydratableInterface, IdentifiableInterface, LoadMetadataInterface, NotifyPropertyChanged, PersistableInterface
{
    public const ONE_TO_ONE_RELATION = 'one-to-one';
    public const ONE_TO_MANY_RELATION = 'one-to-many';
    public const MANY_TO_ONE_RELATION = 'many-to-one';
    public const MANY_TO_MANY_RELATION = 'many-to-many';

    public const EXCLUDE_FROM_MAPPING = ['listeners', 'relations', 'ttl'];

    /** @var int */
    protected $id;

    /** @var int */
    protected $ttl;

    /** @var array */
    protected $relations = [];

    /** @var PropertyChangedListener[] */
    private $listeners = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->onPropertyChanged('id', $this->id, $id);

        $this->id = $id;

        return $this;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    protected function onPropertyChanged(string $propName, $oldValue, $newValue): void
    {
        if ($this->listeners === []) {
            return;
        }

        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }

    public static function loadMetadata(ClassMetadataInterface $metadata): void
    {
        $metadata->setIdentifier(['id']);
        $metadata->setIdentifierFieldNames(['id']);

        foreach (get_class_vars(self::class) as $classVar) {
            if (static::isExcludedForMapping($classVar)) {
                continue;
            }

            $metadata->mapField(['fieldName' => $classVar]);
        }
    }

    /**
     * @param mixed[] $data
     * @see HydratableInterface
     *
     */
    public function hydrate(array $data, ObjectManagerInterface $objectManager): void
    {
        foreach ($data as $field => $value) {
            if (property_exists($this, $field)) {
                if ($relation = $this->relations[$field] ?? null) {
                    $type = $relation['type'];
                    $class = $relation['class'];

                    switch ($type) {
                        case static::ONE_TO_MANY_RELATION:
                            if (empty($value)) {
                                $value = new ArrayCollection();

                                continue;
                            }

                            $values = array_map(
                                function ($val) {
                                    return ['id' => $val];
                                },
                                explode(',', $value)
                            );

                            $value = new ArrayCollection($objectManager->getRepository($class)->findBy($values));
                            break;
                        default:
                            $value = $objectManager->getRepository($class)->find($value);
                            break;
                    }
                }

                $reflectionClass = new \ReflectionClass($this);

                if (null !== $value) {
                    $prop = $reflectionClass->getProperty($field);
                    $prop->setAccessible(true);
                    $prop->setValue($this, $value);
                }
            }
        }
    }

    /**
     * @return mixed[]
     * @see PersistableInterface
     *
     */
    public function preparePersistChangeSet(): array
    {
        $changeSet = $this->prepareChangeSet();

        if ($this->id !== null) {
            $changeSet['id'] = $this->id;
        }

        return $changeSet;
    }

    /**
     * @return mixed[]
     * @see PersistableInterface
     *
     *
     */
    public function prepareUpdateChangeSet(ChangeSet $changeSet): array
    {
        $changeSet = array_map(
            function (Change $change) {
                $propName = $change->getPropertyName();
                $propValue = $change->getNewValue();

                if ($relation = $this->relations[$propName] ?? null) {
                    $type = $relation['type'];

                    $changeSet = [];
                    switch ($type) {
                        case static::ONE_TO_MANY_RELATION:
                            if (is_object($propValue)) {
                                foreach ($propValue as $item) {
                                    $changeSet[] = $item->getId();
                                }
                            }
                            break;
                        default:
                            if (is_object($propValue)) {
                                $changeSet = $propValue->getId();
                            }
                            break;
                    }

                    return $changeSet;
                }

                return $change->getNewValue();
            },
            $changeSet->getChanges()
        );

        $changeSet['id'] = $this->id;

        return $changeSet;
    }

    /**
     * Assign identifier to object.
     *
     * @param mixed[] $identifier
     */
    public function assignIdentifier(array $identifier): void
    {
        $this->id = $identifier['id'];
    }

    private function prepareChangeSet(?ChangeSet $changeSet = null)
    {
        $reflectionClass = new \ReflectionClass(static::class);

        $changeSet = [];
        foreach ($objectVars = $reflectionClass->getProperties() as $property) {
            $propName = $property->getName();
            if ($this->isExcludedForMapping($propName)) {
                continue;
            }

            $property->setAccessible(true);

            $propValue = $property->getValue($this);

            if ($relation = $this->relations[$propName] ?? null) {
                $type = $relation['type'];

                switch ($type) {
                    case static::ONE_TO_MANY_RELATION:
                        $changeSet[$propName] = [];
                        if ($propValue instanceof Model) {
                            foreach ($propValue as $item) {
                                $changeSet[$propName][] = $item->getId();
                            }
                        }
                        break;
                    default:
                        if ($propValue instanceof Model) {
                            $changeSet[$propName] = $propValue->getId();
                        }
                        break;
                }

                continue;
            }

            $changeSet[$propName] = $propValue;
        }

        return $changeSet;
    }

    private static function isExcludedForMapping(string $propName): bool
    {
        return in_array($propName, static::EXCLUDE_FROM_MAPPING);
    }
}