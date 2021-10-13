<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 29/06/2017
 * Time: 16:32
 */

namespace App\Controllers\Integrations\ChargeBee;

use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ChargeBeePaymentSourceController
{
    private $em;
    protected $errorHandler;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->errorHandler = new _ChargeBeeHandleErrors();
    }

    public function listPaymentSourceRoute(Request $request, Response $response)
    {
        $send = $this->listPaymentSources($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function addPaymentSourceRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $uid  = $request->getAttribute('accessUser')['uid'];
        $send = $this->addCardPaymentSource($uid, $body);

        $newCustomerController = new _ChargeBeeCustomerController($this->em);
        $newCustomerController->changePrimaryPayment([
            'uid'       => $uid,
            'paymentId' => $send['message']['id']
        ]);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deletePaymentSourceRoute(Request $request, Response $response)
    {
        $send = $this->deletePaymentSource($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function addDirectDebitPaymentSource(string $customerId, string $mandateId)
    {
        $body['customerId'] = $customerId;
        $body['mandateId']  = $mandateId;
        $createDirectDebit  = function ($body) {
            return \ChargeBee_PaymentSource::createUsingPermanentToken([
                'customer_id'        => $body['customerId'],
                'type'               => 'direct_debit',
                'reference_id'       => $body['mandateId'],
                'gateway_account_id' => 'gw_1mhuIhvQNwgFlf4vt'
            ])->paymentSource()->getValues();
        };

        return $this->errorHandler->handleErrors($createDirectDebit, $body);
    }

    public function addCardPaymentSource(string $customerId, array $body)
    {
        $body['customerId'] = $customerId;
        $addSource          = function ($body) {
            return \ChargeBee_PaymentSource::createCard([
                'customer_id' => $body['customerId'],
                'card'        => $body['card']
            ])->paymentSource()->getValues();
        };

        return $this->errorHandler->handleErrors($addSource, $body);
    }

    public function removePaymentSource($paymentSourceId)
    {
        $deletePaymentSource = function ($paymentId) {
            return \ChargeBee_PaymentSource::delete($paymentId)->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($deletePaymentSource, $paymentSourceId);
    }

    public function listPaymentSources(string $customerId)
    {
        $listSources = function ($customerId) {
            $sources = \ChargeBee_PaymentSource::all([
                'customerId[is]' => $customerId
            ]);

            $paymentSources = [];

            foreach ($sources as $source) {
                $paymentSources[] = $source->paymentSource()->getValues();
            }

            return $paymentSources;
        };

        return $this->errorHandler->handleErrors($listSources, $customerId);
    }

    public function retrievePaymentSource(string $paymentSourceId)
    {
        $listSource = function ($paymentSource) {
            return \ChargeBee_PaymentSource::retrieve($paymentSource)->paymentSource()->getValues();
        };

        return $this->errorHandler->handleErrors($listSource, $paymentSourceId);
    }

    public function deletePaymentSource(string $paymentSourceId)
    {
        $deleteSource = function ($paymentSource) {
            return \ChargeBee_PaymentSource::delete($paymentSource)->customer()->getValues();
        };

        return $this->errorHandler->handleErrors($deleteSource, $paymentSourceId);
    }
}
