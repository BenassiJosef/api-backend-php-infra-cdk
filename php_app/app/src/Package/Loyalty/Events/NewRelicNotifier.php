<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;

class NewRelicNotifier implements EventNotifier
{

    public function notify(LoyaltyStampCardEvent ...$events): void
    {
        foreach ($events as $event) {
            newrelic_record_custom_event(
                "LoyaltyStampEvent",
                $this->attributesFromEvent($event)
            );
        }
    }

    private function attributesFromEvent(LoyaltyStampCardEvent $event): array
    {
        switch ($event->getType()) {
            case LoyaltyStampCardEvent::TYPE_STAMP:
                return (new StampEventMetadata($event))->jsonSerialize();
            default:
                return $event->jsonSerialize();
        }
    }
}
