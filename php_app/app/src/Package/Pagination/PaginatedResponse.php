<?php


namespace App\Package\Pagination;


use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JsonSerializable;
use YaLinqo\Enumerable;

/**
 * Class PaginatedResponse
 * @package App\Package\Pagination
 */
class PaginatedResponse implements JsonSerializable
{
    /**
     * @var int $totalCount
     */
    private $totalCount;

    /**
     * @var Paginator $paginator
     */
    private $paginator;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var $offset
     */
    private $offset;

    /**
     * @var array $body
     */
    private $body;

    /**
     * PaginatedResponse constructor.
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->paginator  = new Paginator($query);
        $this->totalCount = count($this->paginator);
        $this->limit      = $query->getMaxResults();
        $this->offset     = $query->getFirstResult();
        $this->body       = Enumerable::from($this->paginator->getIterator())->toArray();
    }

    /**
     * @return int
     */
    public function maxIndex(): int
    {
        return $this->offset + $this->limit;
    }

    /**
     * @return bool
     */
    public function hasMore(): bool
    {
        return $this->maxIndex() < $this->totalCount;
    }

    /**
     * @return int
     */
    public function nextOffset(): int
    {
        if ($this->hasMore()) {
            return $this->maxIndex();
        }
        return $this->offset;
    }

    /**
     * @return array
     */

    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return array
     */

    public function setBody(array $body)
    {
        $this->body = $body;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'has_more'    => $this->hasMore(),
            'total'       => $this->totalCount,
            'next_offset' => $this->nextOffset(),
            'body'        => $this->getBody()
        ];
    }
}
