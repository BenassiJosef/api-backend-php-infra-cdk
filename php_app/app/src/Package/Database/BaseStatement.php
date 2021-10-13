<?php


namespace App\Package\Database;

/**
 * Class BaseStatement
 * @package App\Package\Database
 */
class BaseStatement implements Statement
{
    /**
     * @var string $query
     */
    private $query;

    /**
     * @var array $parameters
     */
    private $parameters;

    /**
     * BaseStatement constructor.
     * @param string $query
     * @param array $parameters
     */
    public function __construct(
        string $query,
        array $parameters
    ) {
        $this->query      = $query;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function query(): string
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
}