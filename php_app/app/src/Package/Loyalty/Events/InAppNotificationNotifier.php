<?php

namespace App\Package\Loyalty\Events;

use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Notifications\FirebaseNotification;
use App\Package\Notification\InAppNotification;

class InAppNotificationNotifier implements EventNotifier
{
    /**
     * @var InAppNotification $mailSender
     */
    private $inAppNotification;

    /**
     * InAppNotificationNotifier constructor.
     * @param InAppNotification $mailSender
     */
    public function __construct(InAppNotification $inAppNotification)
    {
        $this->inAppNotification = $inAppNotification;
    }

    public function notify(LoyaltyStampCardEvent...$events): void
    {
        foreach ($events as $event) {
            $card = $event->getCard();

            if ($card->isFull()) {
                $notification = new FirebaseNotification(
                    $card->getScheme()->getReward()->getName(),
                    'Reward card full!',
                    [
                        'screen' => 'Schemes',
                        'params' => [
                            'schemeId' => $card->getSchemeId(),
                            'card' => $card->jsonSerialize(),
                        ],
                    ],
                    'loyalty'
                );
                $this->inAppNotification->sendNotificationToProfile(strval($card->getProfileId()), $notification);
                continue;
            }
            $notification = new FirebaseNotification(
                $card->getScheme()->getReward()->getName(),
                "New stamp on your card!",
                [
                    'screen' => 'Schemes',
                    'params' => [
                        'schemeId' => $card->getSchemeId(),
                        'card' => $card->jsonSerialize(),
                    ],
                ],
                'loyalty'
            );
            $this->inAppNotification->sendNotificationToProfile(strval($card->getProfileId()), $notification);

        }
    }
}
