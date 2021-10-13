<?php

namespace App\Package\Segments\Database\Parameters;

use App\Package\Segments\Values\Arguments\Argument;

/**
 * Class SimpleArgumentCanonicaliser
 * @package App\Package\Segments\Database\Parameters
 */
interface ArgumentParameterProvider
{
    /**
     * @param Argument $argument
     * @return string
     */
    public function parameter(Argument $argument): string;
}