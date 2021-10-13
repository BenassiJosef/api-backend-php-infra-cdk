<?php


namespace App\Package\Profile\Data\Filters;


use App\Package\Profile\Data\Filter;

class Allow implements Filter
{
    /**
     * @var string[] $fields
     */
    private $fields;

    /**
     * Allow constructor.
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
            ->where(
                function ($v, $k) use ($fields) {
                    return in_array($k, $fields);
                }
            )
            ->toArray();
    }
}