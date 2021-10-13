<?php

/**
 * Created by jamieaitken on 02/03/2018 at 13:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Billing\Subscriptions;

use App\Models\Integrations\ChargeBee\FailedTransaction;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\OauthUser;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class FailedTransactionController
{
    protected $em;
    protected $nearlyRedis;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyRedis = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function createManualRoute(Request $request, Response $response)
    {

        $send = $this->createManual($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function getAllFailingRoute(Request $request, Response $response)
    {
        $send = $this->getAllFailing();

        return $response->withJson($send, $send['status']);
    }

    public function getAllFailingByCustomerRoute(Request $request, Response $response)
    {
        $send = $this->getAllFailingByCustomer($request->getAttribute('uid'));

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update();

        return $response->withJson($send, $send['status']);
    }

    public function deleteManualRoute(Request $request, Response $response)
    {

        $send = $this->deleteManual($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function createManual(string $serial)
    {

        $create = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(FailedTransaction::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.timesTried >= :three')
            ->setParameter('serial', $serial)
            ->setParameter('three', 3)
            ->getQuery()
            ->getArrayResult();

        if (!empty($create)) {
            return Http::status(400, 'ALREADY_BLOCKED');
        }

        $customerId = $this->em->createQueryBuilder()
            ->select('u.customer_id, u.plan_unit_price')
            ->from(Subscriptions::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.plan_id IN (:locationPlans)')
            ->setParameter('serial', $serial)
            ->setParameter('locationPlans', ['starter', 'starter_an', 'all-in', 'all-in_an', 'demo'])
            ->getQuery()
            ->getArrayResult();

        $create = new FailedTransaction(
            $customerId[0]['customer_id'],
            '',
            '',
            'MANUALLY_SET',
            '',
            $customerId[0]['plan_unit_price']
        );

        $create->serial = $serial;

        $create->timesTried = 3;

        $this->em->persist($create);

        $this->em->flush();

        $this->nearlyRedis->delete($serial . ':paid');

        return Http::status(200);
    }

    public function create(array $invoice, array $transaction)
    {
        $hasFailedBefore = $this->em->createQueryBuilder()
            ->select('u.invoiceId, u.timesTried')
            ->from(FailedTransaction::class, 'u')
            ->where('u.invoiceId = :id')
            ->setParameter('id', $invoice['id'])
            ->getQuery()
            ->getArrayResult();

        $getLineItemsKeys = [];

        foreach ($invoice['line_items'] as $key => $lineItem) {
            $getLineItemsKeys[] = $lineItem['subscription_id'];
        }

        $query = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(Subscriptions::class, 'u')
            ->where('u.subscription_id IN (:s)')
            ->andWhere('u.serial IS NOT NULL')
            ->setParameter('s', $getLineItemsKeys)
            ->getQuery()
            ->getArrayResult();

        if (!empty($hasFailedBefore)) {
            $this->em->createQueryBuilder()
                ->update(FailedTransaction::class, 'u')
                ->set('u.timesTried', ':time')
                ->set('u.customerId', ':customerId')
                ->set('u.transactionId', ':transactionId')
                ->set('u.reasonCode', ':reasonCode')
                ->set('u.reasonText', ':reasonText')
                ->set('u.updatedAt', ':updatedAt')
                ->where('u.invoiceId = :invoiceId')
                ->setParameter('time', $hasFailedBefore[0]['timesTried'] + 1)
                ->setParameter('customerId', $invoice['customer_id'])
                ->setParameter('invoiceId', $hasFailedBefore[0]['invoiceId'])
                ->setParameter('transactionId', $transaction['id'])
                ->setParameter('reasonCode', $transaction['error_code'])
                ->setParameter('reasonText', $transaction['error_text'])
                ->setParameter('updatedAt', new \DateTime())
                ->getQuery()
                ->execute();
        } else {
            foreach ($query as $key => $value) {
                $hasFailedBefore         = new FailedTransaction(
                    $invoice['customer_id'],
                    $invoice['id'],
                    $transaction['id'],
                    isset($transaction['error_code']) ? $transaction['error_code'] : 'N/A',
                    $transaction['error_text'],
                    $transaction['amount']
                );
                $hasFailedBefore->serial = $value['serial'];
                $this->em->persist($hasFailedBefore);
            }
            $this->em->flush();
        }

        return Http::status(200);
    }

    public function get(string $serial)
    {
        $fetch = $this->nearlyRedis->fetch($serial . ':paid');
        if (!is_bool($fetch)) {
            return Http::status(200, $fetch);
        }

        $failed = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(FailedTransaction::class, 'u')
            ->where('u.serial = :s')
            ->andWhere('u.timesTried = :three')
            ->setParameter('s', $serial)
            ->setParameter('three', 3)
            ->getQuery()
            ->getArrayResult();

        $hasPaid = true;

        if (!empty($failed)) {
            $hasPaid = false;
        }

        $this->nearlyRedis->save($serial . ':paid', ['paid' => $hasPaid]);

        return Http::status(200, ['paid' => $hasPaid]);
    }

    public function getAllFailing()
    {
        $failedPayments = $this->em->createQueryBuilder()
            ->select('u.customerId, SUM(u.totalBeingLost) as totalBeingLost, o.email')
            ->from(FailedTransaction::class, 'u')
            ->leftJoin(OauthUser::class, 'o', 'WITH', 'u.customerId = o.uid')
            ->leftJoin(Subscriptions::class, 'su', 'WITH', 'u.serial = su.serial AND u.customerId = su.customer_id')
            ->where('su.status = :active')
            ->setParameter('active', 'active')
            ->orderBy('u.createdAt', 'DESC')
            ->groupBy('u.customerId')
            ->getQuery()
            ->getArrayResult();

        if (empty($failedPayments)) {
            return Http::status(204);
        }

        $response = [
            'totalBeingLost' => 0,
            'customers'      => $failedPayments
        ];

        foreach ($failedPayments as $failedPayment) {
            $response['totalBeingLost'] += $failedPayment['totalBeingLost'];
        }

        return Http::status(200, $response);
    }

    public function getAllFailingByCustomer(string $customerId)
    {
        $failedPayments = $this->em->createQueryBuilder()
            ->select('u')
            ->from(FailedTransaction::class, 'u')
            ->where('u.customerId = :customer')
            ->setParameter('customer', $customerId)
            ->getQuery()
            ->getArrayResult();

        if (empty($failedPayments)) {
            return Http::status(204);
        }

        return Http::status(200, $failedPayments);
    }

    public function deleteManual(string $serial)
    {
        $this->em->createQueryBuilder()
            ->delete(FailedTransaction::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        $this->nearlyRedis->delete($serial . ':paid');

        return Http::status(200);
    }

    public function delete($invoice)
    {
        $getSerialsByInvoice = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(FailedTransaction::class, 'u')
            ->where('u.invoiceId = :id')
            ->setParameter('id', $invoice['id'])
            ->getQuery()
            ->getArrayResult();

        foreach ($getSerialsByInvoice as $serial) {
            $this->nearlyRedis->delete($serial['serial'] . ':paid');
        }

        $this->em->createQueryBuilder()
            ->delete(FailedTransaction::class, 'u')
            ->where('u.invoiceId = :id')
            ->setParameter('id', $invoice['id'])
            ->getQuery()
            ->execute();

        return Http::status(200);
    }
}
