<?php


namespace App\Package\Clients\Delorean\Exceptions;

use App\Package\Clients\Delorean\Request;
use Exception;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class FailedToScheduleJobException
 * @package App\Package\Clients\delorean\Exceptions
 */
class FailedToScheduleJobException extends DeloreanException
{
    /**
     * FailedToScheduleJobException constructor.
     * @param Request $request
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(Request $request, Throwable $previous = null)
    {
        $id = $request->getId();
        $message = $previous->getMessage();
        $line = $previous->getLine();
        $file = $previous->getFile();
        $code = $previous->getCode();
        parent::__construct(
            "Failed to schedule job with id (${id}) got message (${message}) on (${file}:${line}) with code (${code})",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [],
            $previous
        );
    }
}