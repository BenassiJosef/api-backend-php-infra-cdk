<?php


namespace App\Package\Database;

/**
 * Class PaginationStatement
 * @package App\Package\Database
 */
class PaginationStatement implements Statement
{
    /**
     * @var Statement $base
     */
    private $base;

    /**
     * @var int $limit
     */
    private $limit;

    /**
     * @var int $offset
     */
    private $offset;

    /**
     * PaginationStatement constructor.
     * @param Statement $base
     * @param int $limit
     * @param int $offset
     */
    public function __construct(
        Statement $base,
        int $limit,
        int $offset
    ) {
        $this->base   = $base;
        $this->limit  = $limit;
        $this->offset = $offset;
    }

    /**
     * @inheritDoc
     */
    public function query(): string
    {
        $query = string($this->base->query());
        if ($query->endsWith(";")) {
            $query->trim(";");
        }
        return $query->append(" LIMIT :limit OFFSET :offset");
    }

    /**
     * @inheritDoc
     */
    public function parameters(): array
    {
        $parameters =  array_merge(
            $this->base->parameters(),
            [
                'limit'  => $this->limit,
                'offset' => $this->offset,
            ]
        );
        return $parameters;
    }
}