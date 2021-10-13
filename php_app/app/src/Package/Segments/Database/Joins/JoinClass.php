<?php

namespace App\Package\Segments\Database\Joins;

use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidClassException;
use App\Package\Segments\Exceptions\SegmentException;

/**
 * Class JoinClass
 * @package App\Package\Segments\Database\Joins
 */
class JoinClass
{
    /**
     * @param string $className
     * @param string $idPropertyName
     * @return static
     * @throws InvalidClassException
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public static function fromClassName(
        string $className,
        string $idPropertyName = 'id'
    ): self {
        return new self(
            $className,
            $idPropertyName
        );
    }

    /**
     * @param string $className
     * @throws InvalidClassException
     */
    private static function validateClassName(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidClassException($className);
        }
    }

    /**
     * @var string $className
     */
    private $className;

    /**
     * @var string $idPropertyName
     */
    private $idPropertyName;

    /**
     * @var JoinOn[] $joins
     */
    private $joins;

    /**
     * JoinClass constructor.
     * @param string $className
     * @param string $idPropertyName
     * @throws InvalidPropertyException
     * @throws InvalidClassException
     * @throws SegmentException
     */
    public function __construct(
        string $className,
        string $idPropertyName = 'id'
    ) {
        $this->className      = $className;
        $this->idPropertyName = $idPropertyName;
        $this->joins          = [];
        self::validateClassName($className);
        JoinOn::validateClassAndProperty($this, $idPropertyName);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getIdPropertyName(): string
    {
        return $this->idPropertyName;
    }

    /**
     * @return JoinOn[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasProperty(string $propertyName): bool
    {
        return property_exists($this->className, $propertyName);
    }

    /**
     * @param JoinClass $class
     * @return bool
     */
    public function hasJoinToClass(JoinClass $class): bool
    {
        return array_key_exists($class->getClassName(), $this->joins);
    }

    /**
     * @param JoinClass $class
     * @param string|null $propertyName
     * @return $this
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public function toMany(JoinClass $class, ?string $propertyName = null): self
    {
        if ($propertyName === null) {
            $propertyName = $this->referencePropertyName();
        }
        return $this->joinOn($this->idPropertyName, $class, $propertyName);
    }

    /**
     * @param JoinClass $class
     * @param string|null $propertyName
     * @return $this
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public function toOne(JoinClass $class, ?string $propertyName = null): self
    {
        if ($propertyName === null) {
            $propertyName = $class->referencePropertyName();
        }
        return $this->joinOn($propertyName, $class, $class->idPropertyName);
    }

    /**
     * @param string $fromColumn
     * @param JoinClass $toClass
     * @param string $toProperty
     * @return JoinClass
     * @throws InvalidPropertyException
     * @throws SegmentException
     */
    public function joinOn(string $fromColumn, JoinClass $toClass, string $toProperty): self
    {
        $join = new JoinOn(
            $this,
            $fromColumn,
            $toClass,
            $toProperty
        );
        $this->addJoin($join);
        if (!$toClass->hasJoinToClass($this)) {
            $toClass->addJoin($join->inverse());
        }
        return $this;
    }

    /**
     * @param JoinOn $joinOn
     */
    private function addJoin(JoinOn $joinOn)
    {
        $this->joins[$joinOn->getToClass()->getClassName()] = $joinOn;
    }

    /**
     * @param JoinClass $class
     * @return string
     */
    public function joinTypeToClass(JoinClass $class): string
    {
        foreach ($this->joinPathToClass($class) as $joinOn) {
            if ($joinOn->getType() === JoinOn::TYPE_TO_MANY) {
                return JoinOn::TYPE_TO_MANY;
            }
        }
        return JoinOn::TYPE_TO_ONE;
    }

    /**
     * @param JoinClass $class
     * @return JoinOn[]
     */
    public function joinPathToClass(JoinClass $class): array
    {
        if ($class->getClassName() === $this->getClassName()) {
            return [];
        }
        $className = $class->getClassName();
        if (array_key_exists($className, $this->joins)) {
            return [
                $this->joins[$className]
            ];
        }
        foreach ($this->joins as $toClassName => $joinOn) {
            $toClass = $joinOn->getToClass();
            if (!$toClass->canJoinToClass($class)) {
                continue;
            }
            return array_merge(
                [
                    $joinOn,
                ],
                $toClass->joinPathToClass($class)
            );
        }
        return [];
    }

    /**
     * @param JoinClass $class
     * @return bool
     */
    public function canJoinToClass(JoinClass $class): bool
    {
        return array_key_exists(
            $class->getClassName(),
            $this->availableJoins()
        );
    }

    /**
     * @return int[]
     */
    public function availableJoins(): array
    {
        return $this->mergedAvailableJoins();
    }

    /**
     * @param array $alreadySeen
     * @param int $distance
     * @return array
     */
    private function mergedAvailableJoins(array $alreadySeen = [], int $distance = 0): array
    {
        $alreadySeen[$this->className] = true;
        $output                        = [];
        foreach ($this->joins as $className => $joinOn) {
            if (array_key_exists($className, $alreadySeen)) {
                continue;
            }
            $toClass            = $joinOn->getToClass();
            $output[$className] = $distance;
            $availableJoins     = $toClass->mergedAvailableJoins($alreadySeen, $distance + 1);
            $output             = array_merge($output, $availableJoins);
        }
        return $output;
    }

    /**
     * @return string
     */
    public function referencePropertyName(): string
    {
        $parts            = string($this->className)->explode('\\');
        $className        = from($parts)->last();
        $lcfirstClassName = lcfirst($className);
        return "${lcfirstClassName}Id";
    }
}