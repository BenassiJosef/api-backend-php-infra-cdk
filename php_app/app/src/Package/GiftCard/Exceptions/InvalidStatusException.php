<?php


namespace App\Package\GiftCard\Exceptions;


use App\Models\GiftCard;
use Exception;
use Slim\Http\StatusCode;

class InvalidStatusException extends GiftCardException
{
    /**
     * InvalidStatusException constructor.
     * @param string $status
     * @throws Exception
     */
    public function __construct(string $status)
    {
        $availableStatuses = implode(', ', GiftCard::availableStatuses());
        parent::__construct(
            "The status (${status}), is not a valid status for a GiftCard, only (${availableStatuses}) are available.",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'availableStatuses' => GiftCard::availableStatuses(),
            ]
        );
    }
}