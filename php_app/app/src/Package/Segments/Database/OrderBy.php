<?php


namespace App\Package\Segments\Database;


use App\Package\Segments\Fields\Field;

class OrderBy
{
    /**
     * @param Field $field
     * @return static
     */
    public static function asc(Field $field): self
    {
        return new self(
            $field,
            self::ASC
        );
    }

    /**
     * @param Field $field
     * @return static
     */
    public static function desc(Field $field): self
    {
        return new self(
            $field,
            self::DESC
        );
    }

    const ASC = 'asc';

    const DESC = 'desc';

    /**
     * @var Field $field
     */
    private $field;

    /**
     * @var string $ordering
     */
    private $ordering;

    /**
     * OrderBy constructor.
     * @param Field $field
     * @param string $ordering
     */
    private function __construct(Field $field, string $ordering)
    {
        $this->field    = $field;
        $this->ordering = $ordering;
    }

    /**
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getOrdering(): string
    {
        return strtoupper($this->ordering);
    }
}