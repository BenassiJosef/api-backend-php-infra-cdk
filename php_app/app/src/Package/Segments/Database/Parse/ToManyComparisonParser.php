<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Aliases\Exceptions\InvalidClassNameException;
use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidClassException;
use App\Package\Segments\Database\Joins\Exceptions\InvalidPropertyException;
use App\Package\Segments\Database\Parse\Exceptions\InvalidQueryModeException;
use App\Package\Segments\Exceptions\SegmentException;
use App\Package\Segments\Operators\Logic\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class ToManyComparisonParser
 * @package App\Package\Segments\Database\Parse
 */
class ToManyComparisonParser implements ComponentParser
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Context $context
     */
    private $context;

    /**
     * ToManyComparisonParser constructor.
     * @param EntityManager $entityManager
     * @param Context $context
     */
    public function __construct(EntityManager $entityManager, Context $context)
    {
        $this->entityManager = $entityManager;
        $this->context       = $context;
    }

    /**
     * @inheritDoc
     * @throws ClassNotInPoolException
     * @throws InvalidClassException
     * @throws InvalidClassNameException
     * @throws InvalidPropertyException
     * @throws InvalidQueryModeException
     * @throws SegmentException
     */
    public function parse(Container $container)
    {
        $queryBuilder     = $this->queryBuilder();
        $context          = $this
            ->context
            ->subContext($queryBuilder, $container->getAnyComparison());
        $comparisonParser = new ToOneComparisonParser($context);
        $expr             = $queryBuilder->expr();
        $queryBuilder     = $queryBuilder->andWhere(
            $comparisonParser->parse($container)
        );
        $queryBuilder     = $queryBuilder->select(...$context->getSelectColumns());
        return $expr->exists(
            $queryBuilder->getDQL()
        );
    }

    /**
     * @return QueryBuilder
     */
    private function queryBuilder(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder();
    }
}