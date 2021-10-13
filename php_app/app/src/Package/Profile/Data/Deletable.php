<?php


namespace App\Package\Profile\Data;

use App\Package\Database\Statement;

/**
 * Interface Deletable
 * @package App\Package\Profile\Data
 */
interface Deletable extends ObjectDefinition
{
    /**
     * @param Subject $subject
     * @return Statement[]
     */
    public function delete(Subject $subject): array;
}