<?php


namespace App\Package\Response;

/**
 * Interface PaginatableRepository
 * @package App\Package\Response
 */
interface PaginatableRepository
{
    /**
     * @param array $query
     * @return int
     */
    public function count(array $query = []): int;

    /**
     * @param int $offset
     * @param int $limit
     * @param array $query
     * @return array
     */
    public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array;
}