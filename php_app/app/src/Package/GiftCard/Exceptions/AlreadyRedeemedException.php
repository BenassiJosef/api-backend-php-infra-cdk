<?php

namespace App\Package\GiftCard\Exceptions;

use Slim\Http\StatusCode;

/**
 * Class AlreadyRedeemedException
 * @package App\Package\GiftCard\Exceptions
 */
class AlreadyRedeemedException extends GiftCardException
{
    /**
     * AlreadyRedeemedException constructor.
     * @param string $giftCardId
     * @throws \Exception
     */
    public function __construct(string $giftCardId)
    {
        parent::__construct(
            "GiftCard with the id (${giftCardId}) has already been redeemed",
            StatusCode::HTTP_FORBIDDEN,
            [
                'giftCardId' => $giftCardId
            ]
        );
    }
}