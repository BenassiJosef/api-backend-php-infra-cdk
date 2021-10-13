<?php


namespace App\Package\Profile\Data;

use App\Package\Database\Statement;

/**
 * Class DataArea
 * @package App\Package\Profile\Data
 */
class DataArea
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var ObjectDefinition[] $objectDefinitions
     */
    private $objectDefinitions;

    /**
     * Section constructor.
     * @param string $name
     * @param ObjectDefinition[] $objectDefinitions
     */
    public function __construct(
        string $name,
        ObjectDefinition ...$objectDefinitions
    ) {
        $this->name              = $name;
        $this->objectDefinitions = [];
        foreach ($objectDefinitions as $objectDefinition) {
            $this->register($objectDefinition);
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param ObjectDefinition $definition
     * @return DataArea
     */
    public function register(ObjectDefinition $definition): DataArea
    {
        $this->objectDefinitions[$definition->name()] = $definition;
        return $this;
    }

    /**
     * @return array
     */
    public function getObjectDefinitions(): array
    {
        return $this->objectDefinitions;
    }

    /**
     * @return Selectable[]
     */
    public function selectable(): array
    {
        return from($this->objectDefinitions)
            ->where(
                function (ObjectDefinition $definition): bool {
                    return $definition instanceof Selectable;
                }
            )
            ->toArray();
    }

    /**
     * @return Deletable[]
     */
    public function deletable(): array
    {
        return from($this->objectDefinitions)
            ->where(
                function (ObjectDefinition $definition): bool {
                    return $definition instanceof Deletable;
                }
            )
            ->toArray();
    }
}