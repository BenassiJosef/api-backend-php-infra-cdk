<?php

namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Joins\JoinOn;
use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Comparisons\Comparison;
use App\Package\Segments\Operators\Comparisons\LikeComparison;
use App\Package\Segments\Operators\Comparisons\ModifiedComparison;
use App\Package\Segments\Operators\Comparisons\StandardComparison;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use App\Package\Segments\Values\Arguments\Argument;
use App\Package\Segments\Values\MultiValue;
use App\Package\Segments\Values\Value;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison as DoctrineComparison;
use Exception;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;

/**
 * Class ComparisonComponentParser
 * @package App\Package\Segments\Database
 */
class ToOneComparisonParser implements ComponentParser
{
    private static $operatorMap = [
        StandardComparison::EQ   => DoctrineComparison::EQ,
        StandardComparison::NEQ  => DoctrineComparison::NEQ,
        StandardComparison::LT   => DoctrineComparison::LT,
        StandardComparison::LTE  => DoctrineComparison::LTE,
        StandardComparison::GT   => DoctrineComparison::GT,
        StandardComparison::GTE  => DoctrineComparison::GTE,
        LikeComparison::LIKE     => 'LIKE',
        LikeComparison::NOT_LIKE => 'NOT LIKE',
    ];

    /**
     * @var Context $context
     */
    private $context;

    /**
     * ComparisonComponentParser constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     * @throws UnsupportedNodeTypeException
     * @throws InvalidContainerAccessException
     */
    public function parse(Container $container)
    {
        if ($container->getType() !== Container::TYPE_COMPARISON
            && $container->getType() !== Container::TYPE_MODIFIED_COMPARISON
        ) {
            throw new UnsupportedNodeTypeException(
                $container->getType(),
                [
                    Container::TYPE_COMPARISON,
                    Container::TYPE_MODIFIED_COMPARISON
                ]
            );
        }
        $comparison = $container->getAnyComparison();
        return $this->parseComparison($comparison);
    }

    /**
     * @param Comparison $comparison
     * @return DoctrineComparison|Func|Andx|Orx|string
     */
    private function parseComparison(Comparison $comparison)
    {
        $ccp = $this;
        return from($this->valuesFromComparison($comparison))
            ->select(
                function (Value $value) use ($ccp, $comparison) {
                    return $ccp->parseValue($value, $comparison);
                }
            )
            ->aggregate(
                function (Base $logic, $node): Base {
                    $logic->add($node);
                    return $logic;
                },
                $this->multiValueLogicFromComparison($comparison)
            );
    }

    /**
     * @param Value $value
     * @param Comparison $comparison
     * @return DoctrineComparison|Func|Andx|Orx|string
     */
    private function parseValue(Value $value, Comparison $comparison)
    {
        $ccp = $this;
        return from($value->arguments())
            ->select(
                function (Argument $argument) use ($ccp, $comparison): DoctrineComparison {
                    return $ccp->doctrineComparison(
                        $comparison,
                        $argument
                    );
                }
            )
            ->aggregate(
                function (Base $logic, DoctrineComparison $comparison): Base {
                    $logic->add($comparison);
                    return $logic;
                },
                $ccp->individualValueLogicFromComparison($comparison)
            );
    }

    /**
     * @param Comparison $comparison
     * @return Base
     */
    private function individualValueLogicFromComparison(Comparison $comparison): Base
    {
        if ($comparison->getOperator() === StandardComparison::NEQ) {
            return new Orx();
        }
        return new Andx();
    }

    /**
     * @param Comparison $comparison
     * @return Base
     */
    private function multiValueLogicFromComparison(Comparison $comparison): Base
    {
        if ($comparison->getOperator() === StandardComparison::EQ) {
            return new Orx();
        }
        return new Andx();
    }

    /**
     * @param Comparison $comparison
     * @return Value[]
     */
    private function valuesFromComparison(Comparison $comparison): array
    {
        $value = $comparison->getValue();
        if ($value instanceof MultiValue) {
            return $value->values();
        }
        return [$value];
    }

    /**
     * @param Comparison $comparison
     * @param Argument $argument
     * @return DoctrineComparison
     * @throws Exception
     */
    private function doctrineComparison(
        Comparison $comparison,
        Argument $argument
    ): DoctrineComparison {
        if ($comparison instanceof ModifiedComparison) {
            $argument = ValueFormattingArgument::fromLikeModifier(
                $comparison->getModifier(),
                $argument
            );
        }
        return new DoctrineComparison(
            $this->context->propertyName($comparison->getField(), $argument),
            self::$operatorMap[$comparison->getOperator()],
            $this->context->parameter($argument)
        );
    }


}