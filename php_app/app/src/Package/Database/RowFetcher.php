<?php


namespace App\Package\Database;

use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;

/**
 * Interface RowFetcher
 * @package App\Package\Database
 */
interface RowFetcher
{
    /**
     * @param Statement $statement
     * @return array
     * @throws UnsupportedParamTypeException
     * @throws FailedToExecuteStatementException
     */
    public function fetchAll(Statement $statement): array;
}
