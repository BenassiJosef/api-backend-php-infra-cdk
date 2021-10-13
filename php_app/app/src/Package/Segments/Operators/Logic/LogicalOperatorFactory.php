<?php


namespace App\Package\Segments\Operators\Logic;

use App\Package\Segments\Exceptions\UnknownNodeException;
use App\Package\Segments\Operators\Comparisons\ComparisonFactory;
use App\Package\Segments\Operators\Comparisons\ComparisonInput;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicalOperatorException;

/**
 * Class LogicalOperatorFactory
 * @package App\Package\Segments\Operators\Logic
 */
class LogicalOperatorFactory
{
    /**
     * @var ComparisonFactory $comparisonFactory
     */
    private $comparisonFactory;

    /**
     * LogicalOperatorFactory constructor.
     * @param ComparisonFactory $comparisonFactory
     */
    public function __construct(ComparisonFactory $comparisonFactory)
    {
        $this->comparisonFactory = $comparisonFactory;
    }

    /**
     * @param LogicInput $input
     * @return LogicalOperator
     * @throws InvalidLogicalOperatorException
     */
    public function make(LogicInput $input): LogicalOperator
    {
        $nodes                  = $input->getNodes();
        $logicalOperatorFactory = $this;
        $comparisonFactory      = $this->comparisonFactory;
        $containerNodes         = from($nodes)
            ->select(
                function ($node) use ($logicalOperatorFactory, $comparisonFactory) : Container {
                    if ($node instanceof LogicInput) {
                        $logicalOperator = $logicalOperatorFactory
                            ->make(
                                $node
                            );
                        return Container::fromLogicalOperator($logicalOperator);
                    }
                    if ($node instanceof ComparisonInput) {
                        $comparison = $comparisonFactory
                            ->make(
                                $node
                            );
                        return Container::fromComparison($comparison);
                    }
                    throw new UnknownNodeException($node);
                }
            )
            ->toArray();
        return new LogicalOperator(
            $input->getOperator(),
            $containerNodes
        );
    }
}