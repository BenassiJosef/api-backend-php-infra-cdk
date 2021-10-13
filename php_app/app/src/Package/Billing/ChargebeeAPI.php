<?php

/**
 * Created by chrisgreening on 05/03/2020 at 10:57
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace App\Package\Billing;

use App\Utils\Http;
use ChargeBee_HostedPage;
use ChargeBee_Invoice;

class ChargebeeAPI
{

    public function __construct()
    {
        \ChargeBee_Environment::configure(getenv('chargebee_site_name'), getenv('chargebee_site_key'));
    }

    /**
     * @param string $id Id of the chargebee customer
     * @param array $data Data to update
     * @return mixed
     */
    public function updateCustomer(string $id, array $data)
    {
        \ChargeBee_Customer::update($id, $data);
    }

    public function createCustomer(ChargebeeCustomer $chargebeeCustomer)
    {
        $newCustomer = function ($chargebeeCustomer) {
            return \ChargeBee_Customer::create($chargebeeCustomer->toChargebeeCustomer())
                ->customer()
                ->getValues();
        };

        return $this->handleErrors($newCustomer, $chargebeeCustomer);
    }

    public function getSubscription(string $id)
    {
        $request = function ($id) {
            return \ChargeBee_Subscription::retrieve($id);
        };

        return $this->handleErrors($request, $id);
    }

    public function updateSubscription(string $id, array $body)
    {
        $body['subscriptionId'] = $id;
        $request = function ($body) {
            $body['payload']['replace_addon_list'] = true;
            return \ChargeBee_Subscription::update($body['subscriptionId'], $body['payload'])
                ->subscription()
                ->getValues();
        };

        return $this->handleErrors($request, $body);
    }

    public function createSubscription(string $customerId, array $body)
    {
        $body['customerId'] = $customerId;

        $newSubscription = function ($body) {
            // $body['payload']['invoice_immediately'] = false;

            return \ChargeBee_Subscription::createForCustomer($body['customerId'], $body['payload'])
                ->subscription()
                ->getValues();
        };

        return $this->handleErrors($newSubscription, $body);
    }

    public function hostedPage($body)
    {
        $request = function ($body) {
            return \ChargeBee_HostedPage::checkoutExisting($body)->hostedPage()->getValues();
        };

        return $this->handleErrors($request, $body);
    }

    public function hostedNewPage($body)
    {
        $request = function ($body) {
            return \ChargeBee_HostedPage::checkoutNew($body)->hostedPage()->getValues();
        };

        return $this->handleErrors($request, $body);
    }


    public function addCredits($body)
    {
        $request = function ($body) {
            return ChargeBee_Invoice::create($body)->invoice()->getValues();
        };

        return $this->handleErrors($request, $body);
    }

    public function handleErrors($request, $requestParameters)
    {
        try {
            $res = $request($requestParameters);
        } catch (\ChargeBee_PaymentException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_InvalidRequestException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_OperationFailedException $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_APIError $e) {
            return Http::status($e->getHttpStatusCode(), $e->getMessage());
        } catch (\ChargeBee_IOException $e) {
            return Http::status($e->getCurlErrorCode(), $e->getMessage());
        } catch (\Exception $e) {
            return Http::status(500, $e->getMessage());
        }

        return Http::status(200, $res);
    }
}
