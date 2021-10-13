<?php


namespace App\Package\Organisations\Exceptions;

use App\Package\Exceptions\BaseException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Ramsey\Uuid\UuidInterface;

/**
 * Class OrganizationNotFoundException
 * @package App\Package\Organisations\Exceptions
 */
class OrganizationNotFoundException extends BaseException
{
    /**
     * OrganizationNotFoundException constructor.
     * @param UuidInterface $organizationId
     * @throws Exception
     */
    public function __construct(
        UuidInterface $organizationId
    ) {
        parent::__construct(
            "Could not find organization with id (${organizationId})",
            StatusCodes::HTTP_NOT_FOUND,
            [
                'organizationId' => $organizationId->toString(),
            ]
        );
    }
}