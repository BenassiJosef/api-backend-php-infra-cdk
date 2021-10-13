<?php


namespace App\Package\Profile\Data\Filters;


use App\Package\Profile\Data\Filter;
use ChrisUllyott\FileSize;

/**
 * Class DataSize
 * @package App\Package\Profile\Data\Filters
 */
class DataSize implements Filter
{
    /**
     * @var string[] $fields
     */
    private $fields;

    /**
     * DataSize constructor.
     * @param string[] $fields
     */
    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    /**
     * @inheritDoc
     */
    public function filter(array $data): array
    {
        $fields = $this->fields;
        return from($data)
            ->select(
                function ($value, string $key) use ($fields) {
                    if (!in_array($key, $fields)) {
                        return $value;
                    }
                    $fs = new FileSize("$value B");
                    return $fs->asAuto();
                }
            )
            ->toArray();
    }
}