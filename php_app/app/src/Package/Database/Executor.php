<?php


namespace App\Package\Database;


use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;

interface Executor
{
    /**
     * @param Statement $statement
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function execute(Statement $statement);
}