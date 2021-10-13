<?php

namespace App\Package\Upload\Exceptions;

use App\Package\Upload\UploadRow;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidHeadersException
 * @package App\Package\Upload\Exceptions
 */
class InvalidHeadersException extends UploadException
{
    /**
     * InvalidHeadersException constructor.
     * @param array $headers
     * @throws Exception
     */
    public function __construct(array $headers)
    {
        parent::__construct(
            "Invalid CSV Headers",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'gotHeaders'      => $headers,
                'expectedHeaders' => UploadRow::$columnHeaders,
            ]
        );
    }
}