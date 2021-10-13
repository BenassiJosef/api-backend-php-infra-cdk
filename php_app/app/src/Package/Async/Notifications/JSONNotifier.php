<?php

namespace App\Package\Async\Notifications;

use JsonSerializable;

/**
 * Interface JSONNotifier
 * @package App\Package\Async\Notifications
 */
interface JSONNotifier extends Notifier
{
    /**
     * @param array | JsonSerializable $message
     * @return NotificationResponse
     */
    public function notifyJson($message): NotificationResponse;
}