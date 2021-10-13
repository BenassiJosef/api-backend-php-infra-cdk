<?php


namespace App\Package\Organisations\Exceptions;

use App\Package\Exceptions\BaseException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Ramsey\Uuid\UuidInterface;

/**
 * Class OrganizationIdMissingException
 * @package App\Package\Organisations\Exceptions
 */
class OrganizationIdMissingException extends BaseException
{
	/**
	 * OrganizationIdMissingException constructor.
	 * @param UuidInterface $organizationId
	 * @throws Exception
	 */
	public function __construct()
	{
		parent::__construct(
			"Could not find organization without an id",
			StatusCodes::HTTP_BAD_REQUEST
		);
	}
}
