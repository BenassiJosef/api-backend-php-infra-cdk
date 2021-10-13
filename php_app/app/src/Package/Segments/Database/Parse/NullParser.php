<?php

namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;

class NullParser implements ComponentParser
{

    /**
     * @inheritDoc
     */
    public function parse(Container $container)
    {
        return null;
    }
}