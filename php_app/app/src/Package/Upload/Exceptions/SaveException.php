<?php

namespace App\Package\Upload\Exceptions;

use Exception;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class SaveException
 * @package App\Package\Upload\Exceptions
 */
class SaveException extends UploadException
{
    /**
     * SaveException constructor.
     * @param string $message
     * @param Throwable|null $previous
     * @throws Exception
     */
    public function __construct(
        string $message,
        Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            StatusCode::HTTP_BAD_REQUEST,
            [],
            $previous
        );
    }
}