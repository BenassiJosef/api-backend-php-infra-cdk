<?php


namespace App\Models\Loyalty\Exceptions;


use App\Models\Loyalty\LoyaltyStampCard;
use Exception;
use Throwable;

class StampCardException extends Exception
{
    /**
     * StampCardException constructor.
     * @param LoyaltyStampCard $card
     * @param Throwable | null $previous
     */
    public function __construct(LoyaltyStampCard $card, ?Throwable $previous = null)
    {
        $cardId    = $card->getId()->toString();
        $profileId = $card->getProfile()->getId();
        $message   = "cardId (${cardId}) for profile (${profileId})";
        parent::__construct($message, 0, $previous);
    }
}
