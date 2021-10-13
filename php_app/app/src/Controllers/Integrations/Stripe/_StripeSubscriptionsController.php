<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/02/2017
 * Time: 16:57
 */

namespace App\Controllers\Integrations\Stripe;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Stripe\Subscription;

class _StripeSubscriptionsController extends _StripeController
{
    protected $em;

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em = $em;
        parent::__construct($logger, $em);
    }

    public function createSubscription(array $body)
    {
        parent::init(null);
        $createSub = function ($subDetails) {
            return Subscription::create([
                'customer'          => $subDetails['customerId'],
                'items'             => $subDetails['items'],
                'trial_period_days' => $subDetails['trial_period_days'],
                'tax_percent'       => $subDetails['tax_percent'],
                'metadata'          => [
                    'serial' => $subDetails['serial']
                ]
            ]);
        };

        return parent::handleErrors($createSub, $body);
    }

    public function retrieveSubscription(array $body = [])
    {
        parent::init(null);
        $getSub = function ($values) {
            if (isset($values['customerId'])) {
                if (isset($values['startAfter'])) {
                    return Subscription::all([
                        'customer'       => $values['customerId'],
                        'starting_after' => $values['startAfter']
                    ]);
                }

                return Subscription::all([
                    'customer' => $values['customerId']
                ]);
            }

            return Subscription::retrieve($values['id']);
        };

        return parent::handleErrors($getSub, $body);
    }

    public function deleteSubscription(string $subId)
    {
        parent::init(null);
        $deleteSub = function ($id) {
            $sub = Subscription::retrieve($id);

            return $sub->cancel([
                'at_period_end' => true
            ]);
        };

        return parent::handleErrors($deleteSub, $subId);
    }

    public function updateSubscription(string $subId, array $body)
    {
        parent::init(null);
        $updateSub = function ($values, $id) {
            $sub      = Subscription::retrieve($id);
            $annual   = $values['annually'];
            $values   = $values['subscription_items'];
            $subItems = [];
            foreach ($values as $key => $category) {
                if (!empty($category)) {
                    if ($annual === true) {
                        $category['planId'] = $category['planId'] . '_AN';
                    }
                    foreach ($sub->items->data as $item) {
                        if (array_search($category['planId'], array_column($subItems, 'plan')) === false) {
                            if (!isset($category['id'])) {
                                if ($category['planId'] !== 'NONE') {
                                    $subItems[] = [
                                        'plan' => $category['planId']
                                    ];
                                }
                            } else {
                                if ($item->id === $category['subscriptionItemId']) {
                                    if ($item->plan->id === $category['planId']) {
                                    } elseif ($category['planId'] === 'NONE') {
                                        $subItems[] = [
                                            'id'      => $category['subscriptionItemId'],
                                            'deleted' => true
                                        ];
                                    } elseif ($item->plan->id !== $category['planId']) {
                                        $subItems[] = [
                                            'id'   => $category['subscriptionItemId'],
                                            'plan' => $category['planId']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $sub->prorate = true;
            $sub->items   = $subItems;


            return $sub->save();
        };

        return parent::handleErrors($updateSub, $subId, $body);
    }

    public function updateTrialPeriod(string $subId, int $trial)
    {
        $updateSub = function ($id, $trialPeriod) {
            $sub            = Subscription::retrieve($id);
            $sub->trial_end = $trialPeriod;

            return $sub->save();
        };

        return parent::handleErrors($updateSub, $subId, $trial);
    }
}
