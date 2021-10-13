<?php

namespace App\Package\Async\Notifications;

/**
 * Interface Notifier
 * @package App\Package\Async\Notifications
 */
interface Notifier
{
    /**
     * @param string $message
     * @return NotificationResponse
     */
    public function notify(string $message): NotificationResponse;
}