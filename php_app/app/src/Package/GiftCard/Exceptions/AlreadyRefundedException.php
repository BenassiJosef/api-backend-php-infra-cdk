<?php

namespace App\Package\GiftCard\Exceptions;

use Slim\Http\StatusCode;

/**
 * Class AlreadyRefundedException
 * @package App\Package\GiftCard\Exceptions
 */
class AlreadyRefundedException extends GiftCardException
{
    /**
     * AlreadyRefundedException constructor.
     * @param string $giftCardId
     * @throws \Exception
     */
    public function __construct(string $giftCardId)
    {
        parent::__construct(
            "GiftCard with id (${giftCardId}) has already been refunded.",
            StatusCode::HTTP_FORBIDDEN,
            [
                'giftCardId' => $giftCardId,
            ]
        );
    }
}