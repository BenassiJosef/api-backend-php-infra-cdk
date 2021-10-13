<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 29/06/2017
 * Time: 16:31
 */

namespace App\Controllers\Integrations\ChargeBee;

class _ChargeBeeSubscriptionController
{

    protected $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
    }

    public function createSubscription(array $body)
    {
        $newSubscription = function ($body) {
            $body['invoice_immediately'] = false;

            return \ChargeBee_Subscription::createForCustomer($body['customer']['id'], $body)
                ->subscription()
                ->getValues();
        };

        return $this->errorHandler->handleErrors($newSubscription, $body);
    }

    public function updateSubscription(array $body)
    {
        $updateSubscription = function ($body) {

            $basePlan     = $body['planId'];
            $billAnnually = false;
            $addOns       = null;
            if (isset($body['addons'])) {
                $addOns = [$body['addons']];
            }

            if (isset($body['annually'])) {
                $billAnnually = $body['annually'];
            }

            $sendArray = [
                'replaceAddonList'   => true,
                'prorate'            => true,
                'invoiceImmediately' => false
            ];

            $sendArray['planUnitPrice'] = $body['pricing'][$body['planId']];

            if ($billAnnually === true) {
                $basePlan                   = $basePlan . '_an';
                $sendArray['planUnitPrice'] = $body['pricing'][$body['planId']] * 12 - (($body['pricing'][$body['planId']] * 12) * 0.10);
                if (!is_null($addOns)) {
                    foreach ($addOns as $addOn => $addOnList) {
                        foreach ($addOnList as $key => $value) {
                            if (isset($value['id'])) {
                                $addOns[$addOn][$key]['unitPrice'] = $body['pricing'][$value['id']] * 12 - (($body['pricing'][$value['id']] * 12) * 0.10);
                                $addOns[$addOn][$key]['id']        = $addOns[$addOn][$key]['id'] . '_an';
                            }
                        }
                    }
                }
            }

            if (isset($body['planQuantity'])) {
                $sendArray['planQuantity'] = $body['planQuantity'];
            }

            $sendArray['planId'] = $basePlan;
            if (!is_null($addOns)) {
                $sendArray['addons'] = $addOns;
            }

            return \ChargeBee_Subscription::update(
                $body['subId'],
                $sendArray
            )->subscription()->getValues();
        };

        return $this->errorHandler->handleErrors($updateSubscription, $body);
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $deleteSubscription = function ($subId) {
            return \ChargeBee_Subscription::cancel($subId, [
                'endOfTerm' => true
            ])->subscription()->getValues();
        };

        return $this->errorHandler->handleErrors($deleteSubscription, $subscriptionId);
    }

    public function createOneOffCharge(array $body)
    {
        $createCharge = function ($body) {
            return \ChargeBee_Subscription::addChargeAtTermEnd($body['subscriptionId'], [
                'amount'      => $body['amount'],
                'description' => $body['description']
            ])->estimate()->getValues();
        };

        return $this->errorHandler->handleErrors($createCharge, $body);
    }
}
