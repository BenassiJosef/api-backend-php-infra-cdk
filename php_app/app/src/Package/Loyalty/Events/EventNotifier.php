<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;

interface EventNotifier
{
    public function notify(LoyaltyStampCardEvent ...$events): void;
}