<?php


namespace App\Package\Segments\Values;


interface ValueFormatter
{
    /**
     * @return string | int | boolean | null
     */
    public function format();
}