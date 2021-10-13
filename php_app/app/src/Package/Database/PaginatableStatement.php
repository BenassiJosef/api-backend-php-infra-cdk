<?php


namespace App\Package\Database;


class PaginatableStatement implements Statement
{
    /**
     * @var string[] $select
     */
    private $select;

    /**
     * @var string $queryTemplate
     */
    private $queryTemplate;

    /**
     * @var mixed[] $parameters
     */
    private $parameters;

    /**
     * @var int | null $limit
     */
    private $limit;

    /**
     * @var int | null $offset
     */
    private $offset;

    /**
     * PaginatableStatement constructor.
     * @param string[] $select
     * @param string $queryTemplate
     * @param mixed[] $parameters
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(
        array $select,
        string $queryTemplate,
        array $parameters = [],
        ?int $limit = null,
        ?int $offset = null
    ) {
        $this->select        = $select;
        $this->queryTemplate = $queryTemplate;
        $this->parameters    = $parameters;
        $this->limit         = $limit;
        $this->offset        = $offset;
    }


    /**
     * @inheritDoc
     */
    public function query(): string
    {
        $query = sprintf($this->queryTemplate, implode(', ', $this->select));
        if ($this->limit !== null) {
            $query .= " LIMIT :limit";
        }
        if ($this->offset !== null) {
            $query .= " OFFSET :offset";
        }
        return $query . ";";
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function withPagination(int $limit = 25, int $offset = 0): self
    {
        return new self(
            $this->select,
            $this->queryTemplate,
            $this->parameters,
            $limit,
            $offset
        );
    }

    /**
     * @return $this
     */
    public function nextPage(): self
    {
        return new self(
            $this->select,
            $this->queryTemplate,
            $this->parameters,
            $this->limit,
            $this->offset + $this->limit,
        );
    }

    /**
     * @param string $countColumn
     * @return Statement
     */
    public function countStatement(string $countColumn = '*'): Statement
    {
        return new self(
            ["COUNT(${countColumn})"],
            $this->queryTemplate,
            $this->parameters,
            1
        );
    }

    /**
     * @inheritDoc
     */
    public function parameters(): array
    {
        return array_merge(
            $this->parameters,
            $this->limitParameter(),
            $this->offsetParameter()
        );
    }

    private function limitParameter(): array
    {
        if ($this->limit === null) {
            return [];
        }
        return [
            'limit' => $this->limit,
        ];
    }

    private function offsetParameter(): array
    {
        if ($this->offset === null) {
            return [];
        }
        return [
            'offset' => $this->offset,
        ];
    }
}