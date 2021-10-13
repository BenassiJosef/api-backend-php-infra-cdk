<?php


namespace App\Package\Database;


interface SingleResultFetcher
{
    /**
     * @param Statement $statement
     * @return int
     */
    public function fetchSingleIntResult(Statement $statement): int;
}