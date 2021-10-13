<?php


namespace App\Package\Database;


use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;

/**
 * Interface FirstColumnFetcher
 * @package App\Package\Database
 */
interface FirstColumnFetcher
{
    /**
     * @param Statement $statement
     * @return array
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function fetchFirstColumn(Statement $statement): array;
}