<?php

namespace App\Package\Auth\Access\Config\Exceptions;

use App\Models\Organization;
use App\Package\Auth\Exceptions\AuthException;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidOrganizationTypeException
 * @package App\Package\Auth\Access\Config\Exceptions
 */
class InvalidOrganizationTypeException extends AuthException
{
    /**
     * InvalidOrganizationTypeException constructor.
     * @param string $organizationType
     * @throws Exception
     */
    public function __construct(string $organizationType)
    {
        $validOrganizationTypes = implode(', ', Organization::$allTypes);
        parent::__construct(
            "The Organization type (${organizationType} is not a" .
            " valid organization type only (${validOrganizationTypes}) are valid.",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [
                'organizationTypes' => Organization::$allTypes,
            ]
        );
    }
}