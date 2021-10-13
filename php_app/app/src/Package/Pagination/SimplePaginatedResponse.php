<?php


namespace App\Package\Pagination;


use JsonSerializable;

class SimplePaginatedResponse implements JsonSerializable
{
    /**
     * @var array $data
     */
    private $data;

    /**
     * @var int $offset
     */
    private $offset;

    /**
     * @var int $limit
     */
    private $limit;

    /**
     * @var int $total
     */
    private $total;

    /**
     * SimplePaginatedResponse constructor.
     * @param array $data
     * @param int $offset
     * @param int $limit
     * @param int $total
     */
    public function __construct(
        array $data,
        int $offset,
        int $limit,
        int $total
    ) {
        $this->data   = $data;
        $this->offset = $offset;
        $this->limit  = $limit;
        $this->total  = $total;
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
        return $this->maxIndex() < $this->total;
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
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'has_more'    => $this->hasMore(),
            'total'       => $this->total,
            'next_offset' => $this->nextOffset(),
            'body'        => $this->data,
        ];
    }
}