<?php


namespace App\Package\Segments\Database\Parse;

use App\Package\Segments\Database\Parse\Exceptions\UnsupportedNodeTypeException;
use App\Package\Segments\Operators\Logic\Container;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidContainerAccessException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;

/**
 * Class Parser
 * @package App\Package\Segments\Database\Parse
 */
class Parser implements ComponentParser
{
    /**
     * @param Context $context
     * @param EntityManager $entityManager
     * @return static
     */
    public static function fromContextAndEntityManager(
        Context       $context,
        EntityManager $entityManager
    ): self {
        return new self($context, $entityManager);
    }

    /**
     * @var ComponentParser[] $typeToParserMap
     */
    private $typeToParserMap;

    /**
     * Parser constructor.
     * @param Context $context
     * @param EntityManager $entityManager
     */
    public function __construct(Context $context, EntityManager $entityManager)
    {
        $comparisonParser      = new ComparisonParser($context, $entityManager);
        $this->typeToParserMap = [
            Container::TYPE_LOGIC               => new LogicParser($context, $this),
            Container::TYPE_COMPARISON          => $comparisonParser,
            Container::TYPE_MODIFIED_COMPARISON => $comparisonParser,
            Container::TYPE_NULL                => new NullParser(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function parse(Container $container)
    {
        return $this
            ->parser($container)
            ->parse($container);
    }

    private function parser(Container $container): ComponentParser
    {
        return $this->typeToParserMap[$container->getType()];
    }
}