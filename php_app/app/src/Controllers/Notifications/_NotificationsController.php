<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 15/02/2017
 * Time: 09:17
 */

namespace App\Controllers\Notifications;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Slack\_SlackWebhookController;

class _NotificationsController
{
    protected $mail;
    protected $slack;

    public function __construct(_MailController $mail)
    {
        $this->mail = $mail;
    }

    public function paymentFailed($customer)
    {
        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }
        $text  = $name . ' - Payment failed <https://product.stampede.ai/' . $customer['id'] . '/settings>';
        $slack = [
            'text' => $text
        ];

        $slackNotify = new _SlackWebhookController('connect');

        $slackNotify->slackMessage($slack);
    }

    public function didNotConvert($payload, $user)
    {
        $text  = $user['company'] . ' - Red Alert - Did Not Convert From Trial <https://product.stampede.ai/' . $user['uid'] . '/settings>';
        $slack = [
            'text' => $text
        ];

        $mailArgs = self::parseArgs($payload, $user);
        $this->mail->send($mailArgs['sendTo'], $mailArgs['args'], 'TrialCancelled', 'Trial Cancelled');

        $this->slack->slackMessage($slack);
    }

    public function subscriptionCanceled($customer, $serial = null, $kind)
    {

        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }

        $text = $name . ' - Just canceled a subscription (';

        if ($kind === 'hardware') {
            $text .= 'hardware)';
        } elseif ($kind === 'location') {
            $text .= 'location) - <https://product.stampede.ai/' . $serial . '/overview>';
        } elseif ($kind === 'marketing') {
            $text .= 'marketing)';
        } elseif ($kind === 'reviews') {
            $text .= 'reviews)';
        }

        $slack = [
            'text' => $text
        ];

        /*
            $mailArgs = self::parseArgs($payload, $user);
            $this->mail->send($mailArgs['sendTo'], $mailArgs['args'], 'SubscriptionCanceled', 'Subscription Canceled');
        */

        $slackNotify = new _SlackWebhookController('connect');
        $slackNotify->slackMessage($slack);
    }

    public function trialCanceled($customer, $serial)
    {

        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }

        $text = $name . ' -  Did Not Convert From Trial - ' . $serial . ' <https://product.stampede.ai/' . $customer['id'] . '/settings>';

        $slack = [
            'text' => $text
        ];

        /*
            $mailArgs = self::parseArgs($payload, $user);
            $this->mail->send($mailArgs['sendTo'], $mailArgs['args'], 'SubscriptionCanceled', 'Subscription Canceled');
        */

        $slackNotify = new _SlackWebhookController('signup');
        $slackNotify->slackMessage($slack);
    }

    public function chargeFailed($payload, $customer)
    {

        if (!is_null($customer->company) && !empty($customer->company)) {
            $name = $customer->company;
        } else {
            $name = $customer->email;
        }

        $text = $name . ' - Red Alert - Charge failed <https://product.stampede.ai/' . $customer->uid . '/settings>';

        $slack = [
            'text' => $text
        ];

        $mailArgs = self::parseArgs($payload, $customer);

        $this->mail->send($mailArgs['sendTo'], $mailArgs['args'], 'PaymentFailed',
            'Charge Failing - update your Stampede billing information');

        $slackNotify = new _SlackWebhookController('connect');
        $slackNotify->slackMessage($slack);
    }

    public function trialCreated($payload, $customer)
    {
        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }

        $text    = $name . ' - has begun a ' . $payload['cf_method'] . ' (' . $payload['cf_serial'] . ') trial which is due to end at: ';
        $dateEnd = new \DateTime();
        $dateEnd->setTimestamp($payload['trial_end']);
        $dateEndFormatted = $dateEnd->format('g:ia \o\n l jS F Y');
        $text             = $text . $dateEndFormatted;

        $slack = [
            'text' => $text
        ];

        $slackNotify = new _SlackWebhookController('signup');

        $slackNotify->slackMessage($slack);
    }

    public function trialEnding($payload = [], $customer)
    {
        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }

        $text    = $name . ' - Trial ending in 6 days for ' . $payload['cf_serial'] . '. That will be ';
        $dateEnd = new \DateTime();
        $dateEnd->setTimestamp($payload['trial_end']);
        $dateEndFormatted = $dateEnd->format('g:ia \o\n l jS F Y');
        $text             = $text . $dateEndFormatted;

        $slack = [
            'text' => $text
        ];

        $slackNotify = new _SlackWebhookController('signup');

        $slackNotify->slackMessage($slack);
    }

    public function paymentSourceAdded($customer)
    {
        if (!is_null($customer['company']) && !empty($customer['company'])) {
            $name = $customer['company'];
        } else {
            $name = $customer['email'];
        }

        $text = $name . ' - Just added a new payment source.';

        $slack = [
            'text' => $text
        ];

        $slackNotify = new _SlackWebhookController('connect');

        $slackNotify->slackMessage($slack);
    }

    public function invoiceReady($payload = [], $customer = [])
    {
        $text = 'An Invoice is Ready for ' . $customer['company'] . ' <https://product.stampede.ai/members/' . $customer['uid'] . '/invoices/' . $payload['id'] . '>';

        $slack = [
            'text' => $text
        ];

        $mailArgs = self::parseArgs($payload, $customer);

        $this->mail->send($mailArgs['sendTo'], $mailArgs['args'], 'InvoiceReady', 'Your Monthly Invoice For Stampede');

        $this->slack->slackMessage($slack);
    }

    public function parseArgs($payload, $customer)
    {
        $args = [
            'admin'    => $customer['uid'],
            'payload'  => $payload,
            'customer' => $customer
        ];

        $sendTo = [
            [
                'to'   => $customer['email'],
                'name' => $customer['first'] . ' ' . $customer['last']
            ]
        ];

        return [
            'args'   => $args,
            'sendTo' => $sendTo
        ];
    }
}
