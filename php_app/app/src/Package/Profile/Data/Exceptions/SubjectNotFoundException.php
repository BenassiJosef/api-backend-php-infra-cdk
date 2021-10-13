<?php


namespace App\Package\Profile\Data\Exceptions;


use Exception;
use Slim\Http\StatusCode;

/**
 * Class SubjectNotFoundException
 * @package App\Package\Profile\Data\Exceptions
 */
class SubjectNotFoundException extends DataException
{
    /**
     * SubjectNotFoundException constructor.
     * @param string $field
     * @param $value
     * @throws Exception
     */
    public function __construct(
        string $field,
        $value
    ) {
        parent::__construct(
            "Could not find a subject by (${field}) with value (${value}",
            StatusCode::HTTP_NOT_FOUND
        );
    }
}