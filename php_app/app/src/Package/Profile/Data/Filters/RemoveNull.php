<?php

namespace App\Package\Profile\Data\Filters;

use App\Package\Profile\Data\Filter;

/**
 * Class RemoveNull
 * @package App\Package\Profile\Data\Filters
 */
class RemoveNull implements Filter
{
    /**
     * @inheritDoc
     */
    public function filter(array $data): array
    {
        return from($data)
            ->where(
                function ($v): bool {
                    return $v !== null;
                }
            )
            ->toArray();
    }
}