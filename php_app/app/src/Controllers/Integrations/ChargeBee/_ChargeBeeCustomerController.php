<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 29/06/2017
 * Time: 16:30
 */

namespace App\Controllers\Integrations\ChargeBee;

use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\OauthUser;
use App\Package\Billing\ChargebeeCustomer;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ChargeBeeCustomerController
{
    protected $em;
    protected $errorHandler;
    /**
     * @var OrganizationProvider
     */
    private $organizationProvider;
    private $legacyBillingDates = [
        '410ac8a5-81a6-11e7-98e9-040144cf8501',
        'simplelogin:40',
        'simplelogin:82',
        'simplelogin:63',
        'c841e9fd-89a2-4304-8cdf-ff16dbb1c270',
        '2b61d09a-b31f-4d7e-bbe7-9739141980d6',
        '82b91a58-7b79-40b6-986c-f0d25f1ac9af',
        'bd1ef94b-8b38-4017-9a72-1fb7cfa66b02',
        'a7bf6772-4dde-40f4-b1e9-256cffe61f81',
        '7f4c1471-3460-4394-bbe0-da6bb778ec7c',
        '3aee9f1f-1ce6-4f1e-9ea7-c06ddc3b33de',
        'e32bdb2f-99cc-41f0-8213-1809bc931e5d',
        'bd81695c-7ff4-4364-8601-a75ca26f5f88',
        '1063b52f-06f6-4249-9780-3fa7771e47ec',
        'f9db2887-e0c4-446c-b63b-93f3f566eb2d',
        '81adada6-78e7-4f36-97d2-b8f9019ef2b5',
        '91aec143-ae56-4375-97cf-f72814607a27',
        '80c03251-1135-44a6-90c0-a7caff18b448',
        '08af7121-81d9-4a39-a528-bd085ff6b1ca',
        '9e97ed85-c371-4994-82ae-90ba7ef310ca',
        '84005ccc-91a6-4f29-83e1-5a03f0a6be46',
        'ea5ef108-6cfd-4c61-96e6-da1aba9ce3bd',
        '71270ca8-eed9-11e6-b651-040144cf8501',
        '1d183011-2a7a-11e7-b668-040144cf8501',
        '9ca0879b-3017-11e7-b87f-040144cf8501'
    ];

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->errorHandler         = new _ChargeBeeHandleErrors();
        $this->organizationProvider = new OrganizationProvider($em);
    }

    public function getCustomerPaymentSourcesRoute(Request $request, Response $response)
    {
        $body = [
            'id' => $request->getAttribute('accessUser')['uid']
        ];

        $defaultPaymentSource   = $this->getCustomer($body)['message'];
        $newChargePayController = new _ChargeBeePaymentSourceController($this->em);
        $paymentSources         = $newChargePayController->listPaymentSources($body['id']);

        foreach ($paymentSources['message'] as $key => $value) {
            if ($defaultPaymentSource['primary_payment_source_id'] === $value['id']) {
                $paymentSources['message'][$key]['primary'] = true;
            }
        }
        $this->em->clear();

        return $response->withJson($paymentSources, $paymentSources['status']);
    }

    public function createPortalRoute(Request $request, Response $response)
    {
        $newPortalController = new ChargeBeeHostedPageController();

        $org = $this->organizationProvider->organizationForRequest($request);

        $send = $newPortalController->createPortalSession([
            'customer' => [
                'id' => $org->getChargebeeCustomerId()
            ]
        ]);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getCustomerFromChargeBeeRoute(Request $request, Response $response)
    {
        $send = $this->getCustomer([
            'id' => $request->getAttribute('accessUser')['uid']
        ]);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function changeBillingDateRoute(Request $request, Response $response)
    {
        $send = $this->changeBillingDate($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateCustomerAddressRoute(Request $request, Response $response)
    {
        $body        = $request->getParsedBody();
        $body['uid'] = $request->getAttribute('accessUser')['uid'];

        $send = $this->updateCustomerAddress($body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePaymentRoleToPrimaryRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $send = [
            'paymentId' => $body['paymentId'],
            'uid'       => $request->getAttribute('accessUser')['uid']
        ];

        $send = $this->changePrimaryPayment($send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function addPromotionalCreditsRoute(Request $request, Response $response)
    {
        $body               = $request->getParsedBody();
        $body['customerId'] = $request->getAttribute('accessUser')['uid'];

        $send = $this->addPromotionalCreditsForCustomer($body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deletePromotionalCreditsRoute(Request $request, Response $response)
    {
        $body               = $request->getParsedBody();
        $body['customerId'] = $request->getAttribute('accessUser')['uid'];

        $send = $this->deletePromotionalCredits($body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateCustomerAddress(array $body)
    {
        $updateCustomerBilling = function ($body) {
            $id = $body['uid'];
            unset($body['uid']);

            return \ChargeBee_Customer::updateBillingInfo($id, [
                $body
            ])->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($updateCustomerBilling, $body);
    }

    public function createCustomer(ChargebeeCustomer $chargebeeCustomer)
    {
        $newCustomer = function ($chargebeeCustomer) {
            return \ChargeBee_Customer::create($chargebeeCustomer->toChargebeeCustomer())
                ->customer()
                ->getValues();
        };

        return $this->errorHandler->handleErrors($newCustomer, $chargebeeCustomer);
    }

    public function updateCustomer(array $body)
    {
        $updateCustomer = function ($body) {
            $id = $body['id'];
            unset($body['id']);

            return \ChargeBee_Customer::update($id, $body)
                ->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($updateCustomer, $body);
    }

    public function getCustomer(array $body)
    {
        $getCustomer = function ($body) {
            $id = $body['id'];
            unset($body['id']);

            return \ChargeBee_Customer::retrieve($id)
                ->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($getCustomer, $body);
    }

    public function addPromotionalCreditsForCustomer(array $body)
    {
        $addCredits = function ($body) {

            return \ChargeBee_Customer::addPromotionalCredits($body['customerId'], [
                'amount'      => $body['amount'],
                'description' => $body['description']
            ])->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($addCredits, $body);
    }

    public function deletePromotionalCredits(array $body)
    {
        $deleteCredits = function ($body) {
            return \ChargeBee_Customer::deductPromotionalCredits($body['customerId'], [
                'amount'      => $body['amount'],
                'description' => $body['description']
            ]);
        };

        return $this->errorHandler->handleErrors($deleteCredits, $body);
    }

    public function changePrimaryPayment(array $body)
    {
        $updatePayment = function ($body) {
            return \ChargeBee_Customer::assignPaymentRole($body['uid'], [
                'role'              => 'primary',
                'payment_source_id' => $body['paymentId']
            ])->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($updatePayment, $body);
    }

    public function changeBillingDate(array $body)
    {
        if (!in_array($body['uid'], $this->legacyBillingDates)) {
            $hasPriorSubscription = $this->em->getRepository(Subscriptions::class)->findOneBy([
                'customer_id' => $body['uid']
            ], [
                'id' => 'ASC'
            ]);

            if (is_object($hasPriorSubscription)) {
                $newDate = new \DateTime();
                $newDate->setTimestamp($hasPriorSubscription->next_billing_at);
                $billingDate         = (int)$newDate->format('d');
                $body['billingDate'] = $billingDate;
                $changeBillingDate   = function ($body) {
                    return \ChargeBee_Customer::changeBillingDate($body['uid'], [
                        'billingDate'     => $body['billingDate'],
                        'billingDateMode' => 'manually_set'
                    ])->customer()->getValues();
                };

                return $this->errorHandler->handleErrors($changeBillingDate, $body);
            }
        }
    }

    public function createCustomerInChargeBee(OauthUser $user)
    {
        $chargeBeeCustomer = new ChargebeeCustomer($user);

        return $this->createChargebeeCustomer($chargeBeeCustomer);
    }

    public function createChargebeeCustomer(ChargebeeCustomer $chargebeeCustomer): OauthUser
    {
        $createChargeBeeCustomer = $this->createCustomer($chargebeeCustomer);

        $user = $chargebeeCustomer->getUser();
        if ($createChargeBeeCustomer['status'] === 200) {
            $user->setInChargeBee(true);
        }

        return $user;
    }
}
