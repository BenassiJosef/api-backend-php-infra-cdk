<?php


namespace App\Package\Pagination;


use App\Package\Response\PaginatableRepository;
use JsonSerializable;
use Slim\Http\Request;

class RepositoryPaginatedResponse implements JsonSerializable
{
    /**
     * @param Request $request
     * @param PaginatableRepository $paginatableRepository
     * @param int $defaultOffset
     * @param int $defaultLimit
     * @return static
     */
    public static function fromRequestAndRepository(
        Request $request,
        PaginatableRepository $paginatableRepository,
        int $defaultOffset = 0,
        int $defaultLimit = 25
    ): self {
        return new self(
            $request,
            $paginatableRepository,
            $defaultOffset,
            $defaultLimit
        );
    }

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var PaginatableRepository
     */
    private $paginatableRepository;

    /**
     * @var int $defaultOffset
     */
    private $defaultOffset;

    /**
     * @var int $defaultLimit
     */
    private $defaultLimit;

    /**
     * @var null | int $total
     */
    private $total;

    /**
     * RepositoryPaginatedResponse constructor.
     * @param PaginatableRepository $paginatableRepository
     * @param Request $request
     * @param int $defaultOffset
     * @param int $defaultLimit
     */
    public function __construct(
        Request $request,
        PaginatableRepository $paginatableRepository,
        int $defaultOffset,
        int $defaultLimit
    ) {
        $this->request               = $request;
        $this->paginatableRepository = $paginatableRepository;
        $this->defaultOffset         = $defaultOffset;
        $this->defaultLimit          = $defaultLimit;
    }


    private function nextOffset(): int
    {
        if ($this->hasMore()) {
            return $this->maxIndex();
        }
        return $this->offset();
    }

    /**
     * @return bool
     */
    private function hasMore(): bool
    {
        return $this->maxIndex() < $this->total();
    }

    /**
     * @return int
     */
    private function total(): int
    {
        if ($this->total === null) {
            $this->total = $this
                ->paginatableRepository
                ->count(
                    $this->filteredQueryParams()
                );
        }
        return $this->total;
    }

    /**
     * @return int
     */
    private function maxIndex(): int
    {
        return $this->offset() + $this->limit();
    }

    /**
     * @return int
     */
    private function offset(): int
    {
        return $this->request->getQueryParam('offset', $this->defaultOffset);
    }

    /**
     * @return int
     */
    private function limit(): int
    {
        return $this->request->getQueryParam('limit', $this->defaultLimit);
    }

    private function filteredQueryParams(): array
    {
        return from($this->request->getQueryParams())
            ->where(
                function ($param, string $key): bool {
                    return !in_array($key, ['limit', 'offset']);
                }
            )
            ->toArray();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'has_more'    => $this->hasMore(),
            'total'       => $this->total(),
            'next_offset' => $this->nextOffset(),
            'body'        => $this
                ->paginatableRepository
                ->fetchAll(
                    $this->offset(),
                    $this->limit(),
                    $this->filteredQueryParams()
                ),
        ];
    }
}