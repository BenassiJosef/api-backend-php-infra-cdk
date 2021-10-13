<?php


namespace App\Package\GiftCard\Exceptions;

use App\Package\Exceptions\BaseException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Throwable;

/**
 * Class GiftCardNotFoundException
 * @package App\Package\GiftCard\Exceptions
 */
class GiftCardNotFoundException extends GiftCardException
{
    /**
     * GiftCardNotFoundException constructor.
     * @param string $id
     * @throws Exception
     */
    public function __construct(
        string $id
    ) {
        parent::__construct(
            "Gift Card with id (${id}) not found",
            StatusCodes::HTTP_NOT_FOUND
        );
    }
}