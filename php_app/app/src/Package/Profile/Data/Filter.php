<?php


namespace App\Package\Profile\Data;

/**
 * Interface Filter
 * @package App\Package\Profile\Data
 */
interface Filter
{
    /**
     * @param array $data
     * @return array
     */
    public function filter(array $data): array;
}