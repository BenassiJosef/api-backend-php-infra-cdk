<?php

namespace App\Package\Loyalty\Events;

use App\Controllers\Integrations\Mail\MailSender;
use App\Models\Loyalty\LoyaltyStampCardEvent;

class EmailNotifier implements EventNotifier
{
    /**
     * @var MailSender $mailSender
     */
    private $mailSender;

    /**
     * EmailNotifier constructor.
     * @param MailSender $mailSender
     */
    public function __construct(MailSender $mailSender)
    {
        $this->mailSender = $mailSender;
    }

    public function notify(LoyaltyStampCardEvent...$events): void
    {
        foreach ($events as $event) {
            $card = $event->getCard();
            if ($card->isFull()) {
                continue;
            }

            $profile = $card->getProfile();
            $this
                ->mailSender
                ->send(
                    [
                        [
                            'to' => $profile->getEmail(),
                            'name' => $profile->getFullName(),
                        ],
                    ],
                    [
                        'currentCard' => $card->jsonSerialize(),
                        'reward' => $card->getScheme()->getReward()->jsonSerialize(),
                        'design' => $card->getScheme()->jsonSerialize(),
                    ],
                    'LoyaltyStampTemplate',
                    "New stamp on your card!"
                );
        }
    }
}
