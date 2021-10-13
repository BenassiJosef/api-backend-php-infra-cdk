<?php


namespace App\Package\Database\Exceptions;


use App\Package\Database\Statement;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class FailedToExecuteStatementException
 * @package App\Package\Database\Exceptions
 */
class FailedToExecuteStatementException extends DatabaseException
{
    /**
     * FailedToExecuteStatementException constructor.
     * @param Statement $statement
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(Statement $statement, Throwable $previous = null)
    {
        parent::__construct(
            $this->message($statement),
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [
                'parameters' => $statement->parameters(),
            ],
            $previous
        );
    }

    private function message(Statement $statement): string
    {
        $query = $statement->query();
        return "Could not execute the following statement (${query})";
    }
}