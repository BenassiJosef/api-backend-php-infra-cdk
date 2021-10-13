<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;

class NopNotifier implements EventNotifier
{
    public function notify(LoyaltyStampCardEvent ...$events): void
    {
    }
}