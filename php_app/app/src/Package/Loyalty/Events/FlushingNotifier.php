<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Package\Async\Flusher;

class FlushingNotifier implements EventNotifier, Flusher
{

    /**
     * @var EventNotifier $base
     */
    private $base;

    /**
     * @var LoyaltyStampCardEvent[] $events
     */
    private $events;

    /**
     * FlushingNotifier constructor.
     * @param EventNotifier $base
     */
    public function __construct(EventNotifier $base)
    {
        $this->base   = $base;
        $this->events = [];
    }

    /**
     * @param EventNotifier $base
     * @return FlushingNotifier
     */
    public function setBase(EventNotifier $base): FlushingNotifier
    {
        $this->base = $base;
        return $this;
    }

    /**
     * @param LoyaltyStampCardEvent ...$events
     */
    public function notify(LoyaltyStampCardEvent ...$events): void
    {
        $this->events = array_merge($this->events, $events);
    }

    public function flush(): void
    {
        $this->base->notify(...$this->events);
        $this->events = [];
    }
}