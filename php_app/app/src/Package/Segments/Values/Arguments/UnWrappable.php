<?php

namespace App\Package\Segments\Values\Arguments;

/**
 * Interface UnWrappable
 * @package App\Package\Segments\Values\Arguments
 */
interface UnWrappable
{
    /**
     * @return Argument
     */
    public function unWrap(): Argument;
}