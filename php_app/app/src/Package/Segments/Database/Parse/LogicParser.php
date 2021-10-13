<?php

namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Parse\Exceptions\UnsupportedLogicalOperatorException;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use App\Package\Segments\Operators\Logic\LogicalOperator;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;

/**
 * Class AllComponentParser
 * @package App\Package\Segments\Database
 */
class LogicParser implements ComponentParser
{
    /**
     * @param Context $context
     * @return static
     */
    public static function fromContext(Context $context): self
    {
        return new self(
            $context
        );
    }

    /**
     * @var Context $context
     */
    private $context;

    /**
     * @var Parser $parser
     */
    private $parser;

    /**
     * LogicComponentParser constructor.
     * @param Context $context
     * @param Parser $parser
     */
    public function __construct(Context $context, Parser $parser)
    {
        $this->context = $context;
        $this->parser  = $parser;
    }

    /**
     * @inheritDoc
     * @throws InvalidContainerAccessException
     * @throws UnsupportedLogicalOperatorException
     */
    public function parse(Container $container)
    {
        return $this->parseLogicalOperator($container->getLogic());
    }

    /**
     * @param LogicalOperator $operator
     * @return Base|Andx|Orx|string
     * @throws UnsupportedLogicalOperatorException
     */
    private function parseLogicalOperator(LogicalOperator $operator)
    {
        $parser = $this->parser;
        return $this->logicForOperatorWithNodes(
            $operator->getOperator(),
            from($operator->getNodes())
                ->select(
                    function (Container $node) use ($parser) {
                        return $parser->parse($node);
                    }
                )
                ->toArray()
        );
    }

    /**
     * @param string $operator
     * @param Comparison[]|Func[]|Andx[]|Orx[]|string[] $nodes
     * @return Base
     * @throws UnsupportedLogicalOperatorException
     */
    private function logicForOperatorWithNodes(string $operator, array $nodes): Base
    {
        $expr = new Expr();
        switch ($operator) {
            case LogicalOperator::OPERATOR_AND:
                return $expr->andX(...$nodes);
            case LogicalOperator::OPERATOR_OR:
                return $expr->orX(...$nodes);
        }
        throw new UnsupportedLogicalOperatorException(
            $operator,
            array_keys(LogicalOperator::$allowedOperators)
        );
    }
}