<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\AliasedPropertyProvider;
use App\Package\Segments\Database\Aliases\Exceptions\InvalidClassNameException;
use App\Package\Segments\Database\Aliases\TableAliasProvider;
use App\Package\Segments\Database\Joins\ClassPool;
use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidClassException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;
use App\Package\Segments\Database\Joins\JoinClass;
use App\Package\Segments\Database\Joins\JoinOn;
use App\Package\Segments\Database\Parameters\ArgumentCanonicaliser;
use App\Package\Segments\Database\Parameters\ArgumentMapper;
use App\Package\Segments\Database\Parameters\ArgumentParameterProvider;
use App\Package\Segments\Database\Parse\Exceptions\FieldValueMisMatchException;
use App\Package\Segments\Database\Parse\Exceptions\InvalidQueryModeException;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedFieldTypeException;
use App\Package\Segments\Database\BaseQuery;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\DeAliaser;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Fields\MultiProperty;
use App\Package\Segments\Fields\SingleProperty;
use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Values\Arguments\Argument;
use App\Package\Segments\Values\Arguments\UnWrappable;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class Context
 * @package App\Package\Segments\Database\Parse
 */
class Context implements AliasedPropertyProvider, ArgumentParameterProvider, ParameterProvider
{
    /**
     * @param string $mode
     * @param BaseQuery $query
     * @param QueryBuilder $queryBuilder
     * @param Field[] $baseFields
     * @return static
     * @throws ClassNotInPoolException
     * @throws InvalidClassException
     * @throws InvalidPropertyException
     * @throws InvalidQueryModeException
     * @throws SegmentException
     */
    public static function fromMode(
        string $mode,
        BaseQuery $query,
        QueryBuilder $queryBuilder,
        array $baseFields = []
    ): self {
        return new self(
            $mode,
            $query->baseClassName(),
            $queryBuilder,
            array_merge($baseFields, $query->groupByFields())
        );
    }

    /**
     * @param string $mode
     * @throws InvalidQueryModeException
     */
    private static function validateMode(string $mode): void
    {
        if (!array_key_exists($mode, self::$allowedModes)) {
            throw new InvalidQueryModeException($mode, array_keys(self::$allowedModes));
        }
    }

    private static $allowedModes = [
        self::MODE_ALL   => true,
        self::MODE_EMAIL => true,
        self::MODE_SMS   => true,
    ];

    const MODE_ALL = 'all';

    const MODE_EMAIL = 'email';

    const MODE_SMS = 'sms';

    /**
     * @var ClassPool $classPool
     */
    private $classPool;

    /**
     * @var ArgumentCanonicaliser $argumentCanonicaliser
     */
    private $argumentCanonicaliser;

    /**
     * @var TableAliasProvider $tableAliaser
     */
    private $tableAliaser;

    /**
     * @var string $mode
     */
    private $mode;

    /**
     * @var QueryBuilder $queryBuilder
     */
    private $queryBuilder;

    /**
     * @var JoinClass $rootClass
     */
    private $rootClass;

    /**
     * @var Field[] $fields
     */
    private $fields;

    /**
     * @var Context | null $parent
     */
    private $parent;

    /**
     * Context constructor.
     * @param string $mode
     * @param string $rootClassName
     * @param QueryBuilder $queryBuilder
     * @param Field[] $baseFields
     * @param ClassPool|null $classPool
     * @param ArgumentMapper|null $argumentMapper
     * @param TableAliasProvider|null $tableAliasProvider
     * @throws ClassNotInPoolException
     * @throws InvalidClassException
     * @throws InvalidClassNameException
     * @throws InvalidPropertyException
     * @throws InvalidQueryModeException
     * @throws SegmentException
     */
    private function __construct(
        string $mode,
        string $rootClassName,
        QueryBuilder $queryBuilder,
        array $baseFields = [],
        ?ClassPool $classPool = null,
        ?ArgumentMapper $argumentMapper = null,
        ?TableAliasProvider $tableAliasProvider = null
    ) {
        if ($classPool === null) {
            $classPool = ClassPool::default();
        }
        if ($argumentMapper === null) {
            $argumentMapper = new ArgumentMapper();
        }
        if ($tableAliasProvider === null) {
            $tableAliasProvider = new TableAliasProvider();
        }
        self::validateMode($mode);
        $this->classPool             = $classPool;
        $this->argumentCanonicaliser = $argumentMapper;
        $this->tableAliaser          = $tableAliasProvider;
        $this->mode                  = $mode;
        $this->queryBuilder          = $queryBuilder;
        $this->setRootClassAndSelectFrom($rootClassName);
        $this->fields = [];
        foreach ($baseFields as $baseField) {
            $this->fields[$baseField->getKey()] = $baseField;
            $this->updateQueryState($baseField->getAssociatedClass());
        }
    }

    /**
     * @param Field ...$fields
     * @return string[]
     */
    public function aliasPropertyNamesFromField(Field ...$fields): array
    {
        $ctx = $this;
        return from($fields)
            ->selectMany(
                function (Field $field) use ($ctx): array {
                    return from($ctx->propertyNamesFromField($field))
                        ->select(
                            function (string $propertyName) use ($ctx, $field) : string {
                                return $ctx->aliasPropertyNameFromField($field, $propertyName);
                            }
                        )
                        ->toArray();
                }
            )
            ->toArray();
    }

    /**
     * @param Field $field
     * @return array
     */
    private function propertyNamesFromField(Field $field): array
    {
        if ($field instanceof SingleProperty) {
            return [$field->getProperty()];
        }
        if ($field instanceof MultiProperty) {
            return $field->getProperties();
        }
        return [];
    }

    /**
     * @param Field $field
     * @param Argument $argument
     * @return string
     * @throws InvalidClassNameException
     * @throws UnsupportedFieldTypeException
     * @throws FieldValueMisMatchException
     * @throws ClassNotInPoolException
     */
    public function propertyName(Field $field, Argument $argument): string
    {
        $unwrappedArgument = $argument;
        if ($argument instanceof UnWrappable) {
            $unwrappedArgument = $argument->unWrap();
        }
        if ($field instanceof SingleProperty) {
            return $this
                ->aliasPropertyNameFromField(
                    $field,
                    $field->getProperty()
                );
        }
        if ($field instanceof DeAliaser) {
            return $this
                ->aliasPropertyNameFromField(
                    $field,
                    $field->deAlias($unwrappedArgument->getName())
                );
        }
        if (!$field instanceof MultiProperty) {
            throw new UnsupportedFieldTypeException($field);
        }
        self::validateMultiPropertyAndArgument(
            $field,
            $unwrappedArgument
        );
        return $this
            ->aliasPropertyNameFromField(
                $field,
                $argument->getName()
            );
    }

    /**
     * @param Field $field
     * @param string $propertyName
     * @return string
     * @throws ClassNotInPoolException
     * @throws InvalidClassNameException
     */
    private function aliasPropertyNameFromField(Field $field, string $propertyName): string
    {
        if ($this->parent !== null) {
            $this->parent->fields[$field->getKey()] = $field;
        }
        $this->fields[$field->getKey()] = $field;
        return $this
            ->aliasPropertyName($field->getAssociatedClass(), $propertyName);
    }

    /**
     * @param string $className
     * @param string $propertyName
     * @return string
     * @throws ClassNotInPoolException
     * @throws InvalidClassNameException
     */
    public function aliasPropertyName(string $className, string $propertyName): string
    {
        $this->updateQueryState($className);
        return $this
            ->tableAliaser
            ->aliasPropertyName($className, $propertyName);
    }

    /**
     * @param Field $field
     * @return string
     * @throws ClassNotInPoolException
     * @throws InvalidClassNameException
     */
    public function aliasForField(Field $field): string
    {
        $this->updateQueryState($field->getAssociatedClass());
        return $this
            ->tableAliaser
            ->alias($field->getAssociatedClass());
    }

    /**
     * @param string $className
     * @throws ClassNotInPoolException
     * @throws InvalidClassNameException
     */
    private function updateQueryState(string $className): void
    {
        if ($this->tableAliaser->hasClassNameDirectly($className)) {
            return;
        }
        if ($this->parent !== null) {
            $this->parent->updateQueryState($className);
        }
        $this->joinToClass($className);
    }

    private function setRootClassAndSelectFrom(string $className): void
    {
        $this->rootClass = $this->classPool->getClass($className);
        $this
            ->queryBuilder
            ->from(
                $className,
                $this->tableAliaser->alias($className)
            );
    }

    /**
     * @param string $className
     * @throws InvalidClassNameException
     */
    private function selectFromClass(string $className): void
    {
        $this
            ->queryBuilder
            ->from(
                $className,
                $this
                    ->tableAliaser
                    ->alias($className)
            );
    }

    /**
     * @param MultiProperty $field
     * @param Argument $argument
     * @throws FieldValueMisMatchException
     */
    private static function validateMultiPropertyAndArgument(
        MultiProperty $field,
        Argument $argument
    ): void {
        if (!in_array(
            $argument->getName(),
            $field->getProperties()
        )) {
            throw new FieldValueMisMatchException(
                $argument,
                $field
            );
        }
    }

    /**
     * @param string $className
     * @throws ClassNotInPoolException
     * @throws InvalidClassNameException
     */
    private function joinToClass(string $className)
    {
        $unJoinedClass = $this
            ->classPool
            ->getClass($className);
        $joins         = $this
            ->rootClass
            ->joinPathToClass($unJoinedClass);
        foreach ($joins as $join) {
            $this->join($join);
        }
    }

    /**
     * @param JoinOn $joinOn
     * @throws InvalidClassNameException
     */
    private function join(JoinOn $joinOn)
    {
        $this
            ->queryBuilder
            ->leftJoin(
                $joinOn->getToClass()->getClassName(),
                $this->tableAliaser->alias($joinOn->getToClass()->getClassName()),
                Join::WITH,
                $this->joinStringForJoin($joinOn)
            );
    }

    /**
     * @param JoinOn $joinOn
     * @return string
     * @throws InvalidClassNameException
     */
    private function joinStringForJoin(JoinOn $joinOn): string
    {
        $aliasedFromProperty = $this
            ->tableAliaser
            ->aliasPropertyName(
                $joinOn->getFromClass()->getClassName(),
                $joinOn->getFromProperty()
            );

        $aliasedToProperty = $this
            ->tableAliaser
            ->aliasPropertyName(
                $joinOn->getToClass()->getClassName(),
                $joinOn->getToProperty()
            );

        return "${aliasedFromProperty} = ${aliasedToProperty}";
    }

    /**
     * @param Argument $argument
     * @return string
     */
    public function parameter(Argument $argument): string
    {
        $parameterName = $this
            ->argumentCanonicaliser
            ->canonicalise($argument)
            ->getName();
        return ":${parameterName}";
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return array[]
     */
    public function parameters(): array
    {
        return $this
            ->argumentCanonicaliser
            ->parameters();
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return array_values($this->fields);
    }

    /**
     * @return string[]
     */
    private function aliasedPropertyNames(): array
    {
        $ctx = $this;
        return from($this->fields)
            ->toValues()
            ->selectMany(
                function (Field $field) use ($ctx): array {
                    return $ctx->aliasPropertyNamesFromField($field);
                }
            )
            ->toArray();
    }

    public function getSelectColumns(): array
    {
        $ctx             = $this;
        $shouldAggregate = $this->parent === null;
        return from($this->fields)
            ->toValues()
            ->selectMany(
                function (Field $field) use ($ctx, $shouldAggregate): array {
                    $aliasedPropertyNames = $ctx->aliasPropertyNamesFromField($field);
                    return from($aliasedPropertyNames)
                        ->select(
                            function (string $property) use ($field, $shouldAggregate): string {
                                if (!$shouldAggregate) {
                                    return $property;
                                }
                                return $field->formatAsAggregate($property);
                            }
                        )
                        ->toArray();
                }
            )
            ->toArray();
    }

    /**
     * @param array $row
     * @return array
     */
    public function mapRowKeys(array $row): array
    {
        $keys     = $this->aliasedPropertyNames();
        $keyedRow = [];
        foreach ($row as $k => $value) {
            $keyedRow[$keys[$k - 1]] = $value;
        }
        return $keyedRow;
    }


    /**
     * @param Comparison $comparison
     * @return string
     * @throws ClassNotInPoolException
     */
    public function joinTypeToComparison(Comparison $comparison): string
    {
        return $this->joinTypeToField($comparison->getField());
    }

    /**
     * @param Field $field
     * @return string
     * @throws ClassNotInPoolException
     */
    public function joinTypeToField(Field $field): string
    {
        return $this
            ->rootClass
            ->joinTypeToClass(
                $this->getJoinClassFromField(
                    $field
                )
            );
    }

    /**
     * @param Comparison $comparison
     * @return JoinOn
     * @throws ClassNotInPoolException
     */
    private function joinOnForSubContextRoot(Comparison $comparison): JoinOn
    {
        $toClassName = $comparison
            ->getField()
            ->getAssociatedClass();
        $toClass     = $this
            ->classPool
            ->getClass($toClassName);

        $joinPathFromRoot = $this
            ->rootClass
            ->joinPathToClass($toClass);

        /** @var JoinOn $joinOn */
        $joinOn = from($joinPathFromRoot)
            ->first();
        return $joinOn;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Comparison $comparison
     * @return $this
     * @throws ClassNotInPoolException
     * @throws InvalidClassException
     * @throws InvalidClassNameException
     * @throws InvalidPropertyException
     * @throws InvalidQueryModeException
     * @throws SegmentException
     */
    public function subContext(QueryBuilder $queryBuilder, Comparison $comparison): self
    {
        $joinOn             = $this->joinOnForSubContextRoot($comparison);
        $toClass            = $joinOn->getToClass();
        $rootClassName      = $this
            ->rootClass
            ->getClassName();
        $subAliaser         = $this
            ->tableAliaser
            ->subTableAliasProvider($rootClassName);
        $subContext         = new self(
            $this->mode,
            $toClass->getClassName(),
            $queryBuilder,
            [],
            $this->classPool,
            $this->argumentCanonicaliser,
            $subAliaser
        );
        $subContext->parent = $this;
        $fromClass          = $joinOn->getFromClass();
        $aliasedFromColumn  = $subAliaser->aliasPropertyName(
            $fromClass->getClassName(),
            $joinOn->getFromProperty()
        );

        $aliasedToColumn = $subAliaser->aliasPropertyName(
            $toClass->getClassName(),
            $joinOn->getToProperty()
        );
        $queryBuilder
            ->where(
                $queryBuilder->expr()->eq($aliasedFromColumn, $aliasedToColumn)
            );
        return $subContext;
    }

    /**
     * @param Field $field
     * @return JoinClass
     * @throws ClassNotInPoolException
     */
    private function getJoinClassFromField(Field $field): JoinClass
    {
        return $this
            ->classPool
            ->getClass(
                $field->getAssociatedClass()
            );
    }
}
