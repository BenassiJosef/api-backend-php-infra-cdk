<?php

namespace App\Package\Upload\Exceptions;

use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidDataSourceException
 * @package App\Package\Upload\Exceptions
 */
class InvalidDataSourceException extends UploadException
{
    /**
     * InvalidDataSourceException constructor.
     * @param string $key
     * @throws Exception
     */
    public function __construct(
        string $key
    ) {
        parent::__construct(
            "DataSource with key (${key}) cannot be found",
            StatusCode::HTTP_BAD_REQUEST
        );
    }
}