<?php


namespace App\Package\Profile\Data\Filters;


use App\Package\Profile\Data\Filter;

class Merge implements Filter
{
    /**
     * @var string $format
     */
    private $format;

    /**
     * @var string $mergedFieldName
     */
    private $mergedFieldName;

    /**
     * @var string[] $fieldNames
     */
    private $fieldNames;

    /**
     * Merge constructor.
     * @param string $format
     * @param string $mergedFieldName
     */
    public function __construct(string $format, string $mergedFieldName, string ...$fieldNames)
    {
        $this->format          = $format;
        $this->mergedFieldName = $mergedFieldName;
        $this->fieldNames      = $fieldNames;
    }


    /**
     * @inheritDoc
     */
    public function filter(array $data): array
    {
        $extractedFields = $this->extract($data);
        if (count($extractedFields) !== count($this->fieldNames)) {
            return $data;
        }
        $output                         = $this->remove($data);
        $output[$this->mergedFieldName] = sprintf($this->format, ...$extractedFields);
        return $output;
    }


    private function extract(array $data): array
    {
        $output = [];
        foreach ($this->fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $data)) {
                $output[] = $data[$fieldName];
            }
        }
        return $output;
    }

    private function remove(array $data): array
    {
        $fieldNames = $this->fieldNames;
        return from($data)
            ->where(
                function ($v, $k) use ($fieldNames): bool {
                    return !in_array($k, $fieldNames);
                }
            )
            ->toArray();
    }
}