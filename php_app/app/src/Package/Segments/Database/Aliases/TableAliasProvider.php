<?php

namespace App\Package\Segments\Database\Aliases;

use App\Package\Segments\Database\Aliases\Exceptions\InvalidClassNameException;

/**
 * Class TableAliasProvider
 * @package App\Package\Segments\Database
 */
class TableAliasProvider implements TableAliaser
{
    /**
     * @param string $className
     * @throws InvalidClassNameException
     */
    private static function validateClassName(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidClassNameException($className);
        }
    }

    private static $reservedAliases = [
        'or'  => true,
        'and' => true,
    ];

    /**
     * @var string[] $classToAliasMap
     */
    private $classToAliasMap;

    /**
     * @var string[] $aliasToClassMap
     */
    private $aliasToClassMap;

    /**
     * @var TableAliasProvider $parent
     */
    private $parent;

    /**
     * @var TableAliasProvider[] $children
     */
    private $children;

    /**
     * @var bool[] $staticClasses
     */
    private $staticClasses;

    /**
     * TableAliasProvider constructor.
     * @param string ...$classNames
     * @throws InvalidClassNameException
     */
    public function __construct(string ...$classNames)
    {
        $this->aliasToClassMap = [];
        $this->classToAliasMap = [];
        $this->parent          = null;
        $this->children        = [];
        $this->staticClasses   = [];
        foreach ($classNames as $className) {
            $this->alias($className);
        }
    }


    public function subTableAliasProvider(string ...$staticClassNames): self
    {
        $child                = new self();
        $child->parent        = $this;
        $child->staticClasses = array_merge(
            $this->staticClasses,
            from($staticClassNames)
                ->select(
                    function (string $className): bool {
                        self::validateClassName($className);
                        return true;
                    },
                    function (string $className): string {
                        return $className;
                    }
                )
                ->toArray()
        );
        $this->children[]     = $child;
        return $child;
    }

    /**
     * @param string $className
     * @return string
     * @throws InvalidClassNameException
     */
    public function alias(string $className): string
    {
        if ($this->hasClassNameNonRecursive($className)) {
            return $this->classToAliasMap[$className];
        }
        if ($this->parent === null || $this->canReAliasClassName($className)) {
            return $this->addClassName($className);
        }
        return $this->parent->alias($className);
    }

    /**
     * @param string $className
     * @param string $propertyName
     * @return string
     * @throws InvalidClassNameException
     */
    public function aliasPropertyName(string $className, string $propertyName): string
    {
        $alias = $this->alias($className);
        return "${alias}.${propertyName}";
    }

    /**
     * @param string $className
     * @return bool
     */
    public function hasClassName(string $className): bool
    {
        return $this->root()->hasClassNameRecursive($className);
    }

    /**
     * @param string $className
     * @return bool
     */
    public function hasClassNameDirectly(string $className): bool
    {
        if ($this->hasClassNameNonRecursive($className)) {
            return true;
        }
        if ($this->parent !== null) {
            return $this->parent->hasClassNameDirectly($className);
        }
        return false;
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return $this->root()->hasAliasRecursive($alias);
    }

    /**
     * @param string $alias
     * @return bool
     */
    private function aliasIsAvailable(string $alias): bool
    {
        return (!array_key_exists($alias, self::$reservedAliases)) && !$this->hasAlias($alias);
    }

    /**
     * @param string $className
     * @return string
     * @throws InvalidClassNameException
     */
    private function addClassName(string $className): string
    {
        $baseAlias        = self::aliasFromString($className);
        $aliasIsAvailable = $this->aliasIsAvailable($baseAlias);
        if ($aliasIsAvailable) {
            $this->insert($className, $baseAlias);
            return $baseAlias;
        }
        $increment = 1;
        $alias     = "${baseAlias}${increment}";
        while (!$this->aliasIsAvailable($alias)) {
            $increment++;
            $alias = "${baseAlias}${increment}";
        }
        $this->insert($className, $alias);
        return $alias;
    }

    /**
     * @param string $className
     * @param string $alias
     */
    private function insert(string $className, string $alias)
    {
        $this->classToAliasMap[$className] = $alias;
        $this->aliasToClassMap[$alias]     = $className;
    }

    /**
     * @param string $fullClassName
     * @return string
     * @throws InvalidClassNameException
     */
    private static function aliasFromString(string $fullClassName): string
    {
        $parts     = string($fullClassName)->explode("\\");
        $className = from($parts)->last();
        $matches   = [];
        if (!preg_match_all('#([A-Z]+)#', $className, $matches)) {
            throw new InvalidClassNameException($className);
        }
        [, $upperCaseCharacters] = $matches;
        $upperCaseAlias = implode('', $upperCaseCharacters);
        return strtolower($upperCaseAlias);
    }

    private function getAliasFromClassNameNonRecursive(string $className): ?string
    {
        if (!$this->hasClassNameNonRecursive($className)) {
            return null;
        }
        return $this->classToAliasMap[$className];
    }

    private function root(): self
    {
        $root = $this;
        while ($root->parent !== null) {
            $root = $root->parent;
        }
        return $root;
    }

    private function hasClassNameNonRecursive(string $className): bool
    {
        return array_key_exists($className, $this->classToAliasMap);
    }

    private function hasClassNameRecursive(string $className): bool
    {
        if ($this->hasClassNameNonRecursive($className)) {
            return true;
        }
        foreach ($this->children as $child) {
            if ($child->hasClassNameRecursive($className)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $alias
     * @return bool
     */
    private function hasAliasNonRecursive(string $alias): bool
    {
        return array_key_exists($alias, $this->aliasToClassMap);
    }

    /**
     * @param string $alias
     * @return bool
     */
    private function hasAliasRecursive(string $alias): bool
    {
        if ($this->hasAliasNonRecursive($alias)) {
            return true;
        }
        foreach ($this->children as $child) {
            if ($child->hasAliasRecursive($alias)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $className
     * @return bool
     */
    private function canReAliasClassName(string $className): bool
    {
        return !array_key_exists($className, $this->staticClasses);
    }
}