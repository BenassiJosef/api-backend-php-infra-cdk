<?php


namespace App\Package\Database;

/**
 * Interface Statement
 * @package App\Package\Database
 */
interface Statement
{
    /**
     * @return string
     */
    public function query(): string;

    /**
     * @return array
     */
    public function parameters(): array;
}
