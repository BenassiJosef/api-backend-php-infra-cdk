<?php


namespace App\Package\Segments\Database;

use App\Package\Response\PaginatableRepository;
use App\Package\Segments\Database\Joins\JoinOn;
use App\Package\Segments\Database\Parse\LogicParser;
use App\Package\Segments\Database\Parse\ComponentParser;
use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Database\Parse\ParameterProvider;
use App\Package\Segments\Database\Parse\Parser;
use App\Package\Segments\Exceptions\InvalidReachInputException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Fields\Field;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use App\Package\Segments\Reach;
use App\Package\Segments\Segment;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\Query\Expr\OrderBy as DoctrineOrderBy;


/**
 * Class Query
 * @package App\Package\Segments\Database
 */
class Query implements PaginatableRepository
{
    /**
     * @param array $fields
     * @return array
     */
    private static function keyFields(array $fields): array
    {
        return from($fields)
            ->select(
                function (Field $field): Field {
                    return $field;
                },
                function (Field $field): string {
                    return $field->getKey();
                }
            )
            ->toArray();
    }

    /**
     * @var Segment $segment
     */
    private $segment;

    /**
     * @var BaseQuery $baseQuery
     */
    private $baseQuery;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var string $mode
     */
    private $mode;

    /**
     * @var Field[] $baseFields
     */
    private $baseFields;

    /**
     * Query constructor.
     * @param Segment $segment
     * @param BaseQuery $baseQuery
     * @param EntityManager $entityManager
     * @param string $mode
     * @param Field[] $baseFields
     */
    public function __construct(
        Segment $segment,
        BaseQuery $baseQuery,
        EntityManager $entityManager,
        string $mode = Context::MODE_ALL,
        array $baseFields = []
    ) {
        $this->segment       = $segment;
        $this->baseQuery     = $baseQuery;
        $this->entityManager = $entityManager;
        $this->mode          = $mode;
        $this->baseFields    = $baseFields;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return DoctrineQuery
     * @throws InvalidContainerAccessException
     * @throws Parse\Exceptions\UnsupportedLogicalOperatorException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    public function build(int $offset = 0, int $limit = 25): DoctrineQuery
    {
        $queryBuilder = $this->queryBuilder();
        return $this->buildForContext(
            $queryBuilder,
            $this->context($queryBuilder)
        )
                    ->setFirstResult($offset)
                    ->setMaxResults($limit);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws InvalidContainerAccessException
     * @throws Parse\Exceptions\UnsupportedLogicalOperatorException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    public function getResults(int $offset = 0, int $limit = 25): array
    {
        $queryBuilder = $this->queryBuilder();
        $context      = $this->context($queryBuilder);
        $query        = $this->buildForContext(
            $queryBuilder,
            $context
        )
                             ->setFirstResult($offset)
                             ->setMaxResults($limit);
        $results      = $query->getArrayResult();
        return from($results)
            ->select(
                function (array $row) use ($context): array {
                    $fields = $context->getFields();
                    $row    = $context->mapRowKeys($row);
                    return from($fields)
                        ->select(
                            function (Field $field) use ($row, $context) {
                                $formattedResult = $field
                                    ->fromArray(
                                        $row,
                                        $context->aliasForField($field)
                                    );
                                if ($context->joinTypeToField($field) === JoinOn::TYPE_TO_ONE) {
                                    return $formattedResult;
                                }
                                if ($field->aggregateFunction() !== 'GROUP_CONCAT') {
                                    return $formattedResult;
                                }
                                return explode(',', $formattedResult);
                            },
                            function (Field $field): string {
                                return $field->getKey();
                            }
                        )
                        ->toArray();
                }
            )
            ->toArray();
    }

    /**
     * @param array $query
     * @return int
     * @throws InvalidContainerAccessException
     * @throws Joins\Exceptions\ClassNotInPoolException
     * @throws Joins\Exceptions\InvalidClassException
     * @throws Joins\Exceptions\InvalidPropertyException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Parse\Exceptions\InvalidQueryModeException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    public function count(array $query = []): int
    {
        $queryBuilder    = $this->queryBuilder();
        $context         = $this->context($queryBuilder);
        $queryBuilder    = $this->buildWhere(
            $queryBuilder,
            $context
        );
        $countProperties = $context
            ->aliasPropertyNamesFromField(
                ...$this->baseQuery->groupByFields()
            );
        $countProperty   = from($countProperties)->first();
        $queryBuilder    = $queryBuilder
            ->select("COUNT(DISTINCT ${countProperty})");
        return $this
            ->setParameters($queryBuilder, $context)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Reach
     * @throws InvalidContainerAccessException
     * @throws Joins\Exceptions\ClassNotInPoolException
     * @throws Joins\Exceptions\InvalidClassException
     * @throws Joins\Exceptions\InvalidPropertyException
     * @throws Parse\Exceptions\InvalidQueryModeException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     * @throws SegmentException
     * @throws InvalidReachInputException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function reach(): Reach
    {
        $queryBuilder = $this->queryBuilder();
        $context      = $this->context($queryBuilder);
        $queryBuilder = $this->buildWhere($queryBuilder, $context);
        $queryBuilder = $queryBuilder->select(...$this->baseQuery->reachSelect($context));
        $queryBuilder = $this->setParameters($queryBuilder, $context);
        $query        = $queryBuilder->getQuery();
        $results      = $query->getSingleResult(DoctrineQuery::HYDRATE_ARRAY);
        $results      = from($results)
            ->select(
                function (string $stringVal): int {
                    return intval($stringVal);
                }
            )
            ->toArray();
        return Reach::fromArray(
            [
                'version' => Reach::VERSION,
                'reach'   => $results,
            ]
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return Context
     * @throws Joins\Exceptions\ClassNotInPoolException
     * @throws Joins\Exceptions\InvalidClassException
     * @throws Joins\Exceptions\InvalidPropertyException
     * @throws Parse\Exceptions\InvalidQueryModeException
     * @throws SegmentException
     */
    private function context(QueryBuilder $queryBuilder): Context
    {
        return Context::fromMode(
            $this->mode,
            $this->baseQuery,
            $queryBuilder,
            array_merge(
                self::keyFields($this->baseFields),
                self::keyFields($this->baseQuery->baseFields())
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Context $context
     * @return DoctrineQuery
     * @throws InvalidContainerAccessException
     * @throws Parse\Exceptions\UnsupportedLogicalOperatorException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    private function buildForContext(QueryBuilder $queryBuilder, Context $context): DoctrineQuery
    {
        $queryBuilder = $this
            ->buildWhere(
                $queryBuilder,
                $context
            )
            ->select(...$context->getSelectColumns());
        $queryBuilder = $this
            ->setParametersAndGroupBy($queryBuilder, $context);
        $queryBuilder = $this->buildOrderBy($queryBuilder, $context);

        return $queryBuilder->getQuery();
    }

    private function buildOrderBy(QueryBuilder $queryBuilder, Context $context): QueryBuilder
    {
        /** @var DoctrineOrderBy[] $ordering */
        $ordering = from($this->baseQuery->ordering())
            ->select(
                function (OrderBy $orderBy) use ($context) : array {
                    $field      = $orderBy->getField();
                    $properties = $context->aliasPropertyNamesFromField($field);
                    return from($properties)
                        ->select(
                            function (string $property) use ($orderBy): DoctrineOrderBy {
                                return new DoctrineOrderBy($property, $orderBy->getOrdering());
                            }
                        )
                        ->toArray();
                }
            )
            ->selectMany()
            ->toArray();
        foreach ($ordering as $orderBy) {
            $queryBuilder = $queryBuilder->orderBy($orderBy);
        }
        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Context $context
     * @return QueryBuilder
     * @throws InvalidContainerAccessException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    private function buildWhere(QueryBuilder $queryBuilder, Context $context): QueryBuilder
    {
        $parser        = Parser::fromContextAndEntityManager(
            $context,
            $this->entityManager
        );
        $rootContainer = $this->segment->getRootAsContainer();
        $where  = $parser->parse($rootContainer);

        $queryBuilder = $this
            ->baseQuery
            ->queryBuilder($queryBuilder, $context);
        if ($where !== null) {
            $queryBuilder = $queryBuilder->andWhere($where);
        }
        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Context $context
     * @return QueryBuilder
     */
    private function setParametersAndGroupBy(QueryBuilder $queryBuilder, Context $context): QueryBuilder
    {
        return $this
            ->setParameters($queryBuilder, $context)
            ->groupBy(
                ...$context->aliasPropertyNamesFromField(
                ...$this->baseQuery->groupByFields()
            )
            );
    }

    private function setParameters(QueryBuilder $queryBuilder, Context $context): QueryBuilder
    {
        return $queryBuilder
            ->setParameters(
                $context->parameters()
            );
    }

    private function queryBuilder(): QueryBuilder
    {
        return $this
            ->entityManager
            ->createQueryBuilder();
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param array $query
     * @return array
     * @throws InvalidContainerAccessException
     * @throws Parse\Exceptions\UnsupportedLogicalOperatorException
     * @throws Parse\Exceptions\UnsupportedNodeTypeException
     */
    public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array
    {
        return $this->getResults($offset, $limit);
    }
}