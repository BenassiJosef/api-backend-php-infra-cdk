<?php

namespace App\Package\Segments\Database\Parameters;

use App\Package\Segments\Database\Parse\ParameterProvider;
use App\Package\Segments\Values\Arguments\Argument;

/**
 * Interface ArgumentCanonicaliser
 * @package App\Package\Segments\Database\Parameters
 */
interface ArgumentCanonicaliser extends ParameterProvider
{
    /**
     * @param Argument $argument
     * @return Argument
     */
    public function canonicalise(Argument $argument): Argument;

    /**
     * @return Argument[]
     */
    public function arguments(): array;

}