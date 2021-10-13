<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/06/2017
 * Time: 15:46
 */

namespace App\Controllers\Billing\Webhooks;

use App\Controllers\Billing\Subscription;
use App\Controllers\Billing\Subscriptions\LocationSubscriptionController;
use App\Controllers\Billing\Subscriptions\FailedTransactionController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeEventController;
use App\Controllers\Integrations\ChargeBee\ChargeBeeEventGetter;
use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Locations\_LocationCreationController;
use App\Controllers\Notifications\_NotificationsController;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Package\Billing\SMSTransactions;
use App\Package\Organisations\OrganizationService;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Slim\Http\Response;
use Slim\Http\Request;

class ChargeBeeWebHookController
{
    protected $em;
    protected $mail;
    private   $client;
    protected $connectCache;

    /**
     * @var ChargeBeeEventGetter
     */
    private $chargebeeEventController;

    /**
     * @var SMSTransactions
     */
    private $smsTransactionService;

    /**
     * @var OrganizationService $organizationService
     */
    private $organizationService;
    /**
     * @var LocationSubscriptionController
     */
    private $subscriptionController;

    public function __construct(
        EntityManager $em,
        _MailController $mail,
        ChargeBeeEventGetter $chargeBeeEventController = null,
        LocationSubscriptionController $subscriptionController,
        SMSTransactions $smsTransactionService
    ) {
        $this->em           = $em;
        $this->mail         = $mail;
        $this->smsTransactionService = $smsTransactionService;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
        if ($chargeBeeEventController === null) {
            $chargeBeeEventController = new _ChargeBeeEventController();
        }

        $this->organizationService = new OrganizationService($em);
        $this->chargebeeEventController = $chargeBeeEventController;
        $this->subscriptionController = $subscriptionController;
    }


    public function getOrgIdFromSubscriptionEvent(array $event): ?string
    {
        $subscription = $event['content']['subscription'];
        $customer = $event['content']['customer'];
        if (array_key_exists('cf_organisation_id', $subscription)) {
            return $subscription['cf_organisation_id'];
        }
        /**
         * @var Organization $organization
         */
        $organization = $this->em->getRepository(Organization::class)->findOneBy([
            'chargebeeCustomerId' => $customer['id']
        ]);
        if (!is_null($organization)) {
            return $organization->getId()->toString();
        }
        return null;
    }

    public function getOrgFromCustomerId(string $customerId): ?Organization
    {
        /**
         * @var Organization $organization
         */
        $organization = $this->em->getRepository(Organization::class)->findOneBy([
            'chargebeeCustomerId' => $customerId
        ]);

        return  $organization;
    }

    public function receiveWebHook(Request $request, Response $response)
    {
        $message         = $request->getParsedBody();

        /*
        $event        = $this
            ->chargebeeEventController
            ->getEvent($body['id'], $body);
*/

        $mp           = new _Mixpanel();
        $this->client = new QueueSender();

        if (strpos($message['event_type'], 'subscription') !== false) {
            $message['content']['subscription']['cf_organisation_id'] = $this->getOrgIdFromSubscriptionEvent($message);
            $subscription    = $message['content']['subscription'];
            $organisationSubscription = new Subscription($this->organizationService, $this->em);
            $subscriptionResponse = $organisationSubscription->webhook($subscription, $message['event_type']);
            $this->connectCache->deleteMultiple(
                [
                    $subscription['customer_id'] . ':location:accessibleLocations',
                    $subscription['customer_id'] . ':marketing:accessibleLocations',
                    $subscription['customer_id'] . ':connectNotificationLists',
                    $subscription['customer_id'] . ':profile'
                ]
            );
            return $response->withJson($subscriptionResponse);
        }

        switch ($message['event_type']) {
            case 'invoice_generated':

                $lineItems = $message['content']['invoice']['line_items'];
                $creditsToAdd = 0;
                foreach ($lineItems as $key => $lineItem) {
                    if (!str_contains($lineItem['entity_id'], 'sms-credits')) {
                        continue;
                    }
                    $creditsToAdd += $lineItem['quantity'];
                }
                if ($creditsToAdd > 0) {
                    $organization = $this->getOrgFromCustomerId($message['content']['invoice']['customer_id']);
                    if (!is_null($organization)) {
                        $this->smsTransactionService->addCredits($organization, $creditsToAdd);
                    }
                }
                return $response->withJson($message);
                break;
                //Triggered when a customer is created.
            case 'customer_created':
                break;
            case 'customer_changed':
                break;
            case 'customer_deleted':
                $content = $message['content']['customer'];

                $getCustomerSubscriptions = $this->em->createQueryBuilder()
                    ->select('u')
                    ->from(Subscriptions::class, 'u')
                    ->where('u.customer_id = :id')
                    ->setParameter('id', $content['id'])
                    ->getQuery()
                    ->getArrayResult();
                $deassignController       = new _LocationCreationController($this->em);
                foreach ($getCustomerSubscriptions as $customerSubscription) {
                    if (!is_null($customerSubscription['serial'])) {
                        $deassignController->locationAccessController->deassignAccess(
                            $customerSubscription['serial']
                        );
                    }
                }
                break;

                //Triggered when a new subscription is created.


                //Triggered when a payment source is added.
            case 'payment_source_added':
                $mp->identify($message['content']['customer']['id'])->track(
                    'payment_source_created',
                    $message
                );

                $notifications = new _NotificationsController($this->mail);

                $notifications->paymentSourceAdded($message['content']['customer']);
                break;
            case 'payment_succeeded':
                $invoice = $message['content']['invoice'];

                $failedTransactionController = new FailedTransactionController($this->em);
                $failedTransactionController->delete($invoice);

                break;
                //Triggered when attempt to charge customer's credit card fails
            case 'payment_failed':
                $invoice = $message['content']['invoice'];

                $failedTransactionController = new FailedTransactionController($this->em);
                $failedTransactionController->create($invoice, $message['content']['transaction']);

                $notifications = new _NotificationsController($this->mail);

                $this->client->sendMessage(
                    [
                        'notificationContent' => [
                            'objectId' => $invoice['id'],
                            'title'    => 'Payment Failed',
                            'kind'     => 'billing_error',
                            'link'     => 'payment_method'
                        ],
                        'pushMethod'          => [
                            'kind' => 'specific',
                            'uid'  => $message['content']['customer']['id']
                        ]
                    ],
                    QueueUrls::NOTIFICATION
                );

                $notifications->paymentFailed($message['content']['customer']);
                break;
            case 'card_expiry_reminder':
                $customer = $message['content']['customer'];

                $this->client->sendMessage(
                    [
                        'notificationContent' => [
                            'objectId' => $customer['id'],
                            'title'    => 'Card Expiring',
                            'kind'     => 'card_expiry_reminder',
                            'link'     => 'payment_method'
                        ],
                        'pushMethod'          => [
                            'kind' => 'specific',
                            'uid'  => $customer['id']
                        ]
                    ],
                    QueueUrls::NOTIFICATION
                );

                break;
        }


        $this->em->clear();

        return $response->withJson($event);
    }
}
