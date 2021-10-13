<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;

/**
 * Interface ComponentParser
 * @package App\Package\Segments\Database
 */
interface ComponentParser
{
    /**
     * @param Container $container
     * @return Comparison|Func|Andx|Orx|string
     * @throws UnsupportedNodeTypeException
     * @throws InvalidContainerAccessException
     */
    public function parse(Container $container);
}