<?php


namespace App\Package\Segments\Operators\Logic\Exceptions;


use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Throwable;

/**
 * Class NotEnoughNodesException
 * @package App\Package\Segments\Operators\Logic\Exceptions
 */
class NotEnoughNodesException extends BaseException
{
    /**
     * NotEnoughNodesException constructor.
     * @param string $operator
     * @param array $nodes
     */
    public function __construct(string $operator, array $nodes)
    {
        $nodesString = json_encode($nodes);
        $nodesCount  = count($nodes);
        parent::__construct(
            "Operator of type (${operator}) with nodes "
            . "(${nodesString}) has (${nodesCount}) nodes, the minimum is 2",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}