<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Database\Joins\JoinOn;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;

class ComparisonParser implements ComponentParser
{
    /**
     * @var Context $context
     */
    private $context;

    /**
     * @var ComponentParser[] $joinTypeToParserMap
     */
    private $joinTypeToParserMap;

    public function __construct(Context $context, EntityManager $entityManager)
    {
        $this->context             = $context;
        $this->joinTypeToParserMap = [
            JoinOn::TYPE_TO_ONE  => new ToOneComparisonParser($context),
            JoinOn::TYPE_TO_MANY => new ToManyComparisonParser($entityManager, $context),
        ];
    }

    /**
     * @param Container $container
     * @return Comparison|Func|Andx|Orx|string
     * @throws UnsupportedNodeTypeException
     * @throws InvalidContainerAccessException
     * @throws ClassNotInPoolException
     */
    public function parse(Container $container)
    {
        return $this
            ->parser($container)
            ->parse($container);
    }

    /**
     * @param Container $container
     * @return ComponentParser
     * @throws InvalidContainerAccessException
     * @throws ClassNotInPoolException
     */
    private function parser(Container $container): ComponentParser
    {
        $comparison = $container->getAnyComparison();
        $joinType = $this->context->joinTypeToComparison($comparison);
        return $this->joinTypeToParserMap[$joinType];
    }

}