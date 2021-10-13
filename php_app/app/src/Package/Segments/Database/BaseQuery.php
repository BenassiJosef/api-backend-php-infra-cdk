<?php


namespace App\Package\Segments\Database;

use App\Package\Segments\Database\Parse\Context;
use Doctrine\ORM\QueryBuilder;
use App\Package\Segments\Fields\Field;

interface BaseQuery
{
    /**
     * @return Field[]
     */
    public function baseFields(): array;

    /**
     * @return string
     */
    public function baseClassName(): string;

    /**
     * @param QueryBuilder $builder
     * @param Context $context
     * @return QueryBuilder
     */
    public function queryBuilder(
        QueryBuilder $builder,
        Context $context
    ): QueryBuilder;

    /**
     * @return Field[]
     */
    public function groupByFields(): array;

    /**
     * @return OrderBy[]
     */
    public function ordering(): array;

    /**
     * @param Context $context
     * @return string[]
     */
    public function reachSelect(Context $context): array;
}
