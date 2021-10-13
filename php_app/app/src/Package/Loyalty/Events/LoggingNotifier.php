<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggingNotifier implements EventNotifier
{
    /**
     * @var string $prefix
     */
    private $prefix;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * LoggingNotifier constructor.
     * @param string $prefix
     * @param LoggerInterface $logger
     */
    public function __construct(
        ?LoggerInterface $logger = null,
        string $prefix = 'stamp_card'
    ) {
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        $this->prefix = $prefix;
    }


    public function notify(LoyaltyStampCardEvent ...$events): void
    {
        foreach ($events as $event) {
            $type = $event->getType();
            $prefix = $this->prefix;
            $this->logger->info("${prefix}_${type}", $event->jsonSerialize());
        }
    }
}