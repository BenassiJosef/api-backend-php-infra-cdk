<?php

namespace App\Package\Profile\Data;

use App\Package\Database\Statement;

/**
 * Interface Selectable
 * @package App\Package\Profile\Data\Presentation
 */
interface Selectable extends ObjectDefinition
{
    /**
     * @param Subject $subject
     * @return Statement
     */
    public function select(Subject $subject): Statement;
}