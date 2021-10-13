<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;

class Router implements EventNotifier
{
    /**
     * @var EventNotifier[][]
     */
    private $notifiers;

    /**
     * Router constructor.
     * @param EventNotifier[][] $notifiers
     */
    public function __construct(array $notifiers = [])
    {
        foreach (LoyaltyStampCardEvent::$allTypes as $eventType) {
            if (array_key_exists($eventType, $notifiers)) {
                continue;
            }
            $notifiers[$eventType] = [];
        }
        $this->notifiers = $notifiers;
    }

    /**
     * @param LoyaltyStampCardEvent ...$events
     */
    public function notify(LoyaltyStampCardEvent ...$events): void
    {
        foreach ($events as $event) {
            foreach ($this->notifiers[$event->getType()] as $eventNotifier) {
                $eventNotifier->notify($event);
            }
        }
    }

    /**
     * @param EventNotifier $notifier
     * @param string ...$eventTypes
     */
    public function register(EventNotifier $notifier, string ...$eventTypes): self
    {
        if (count($eventTypes) === 0) {
            $eventTypes = LoyaltyStampCardEvent::$allTypes;
        }
        foreach ($eventTypes as $eventType) {
            if (!array_key_exists($eventType, $this->notifiers)) {
                $this->notifiers[$eventType] = [];
            }
            $this->notifiers[$eventType][] = $notifier;
        }
        return $this;
    }
}
