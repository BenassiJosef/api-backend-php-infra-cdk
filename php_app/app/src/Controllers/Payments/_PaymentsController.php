<?php

namespace App\Controllers\Payments;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\Stripe\_StripeChargeController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Controllers\Registrations\_ValidationController;
use App\Models;
use App\Models\UserPayments;
use App\Utils\Http;
use App\Utils\PushNotifications;
use DateTime;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 03/01/2017
 * Time: 13:35
 */
class _PaymentsController
{

    protected $em;
    protected $logger;
    protected $mixpanel;


    public function __construct(Logger $logger, EntityManager $em, _Mixpanel $mixpanel)
    {
        $this->em       = $em;
        $this->logger = $logger;
        $this->mixpanel = $mixpanel;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */

    public function createPaymentRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->createPayment($body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePaymentRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->updatePayment($request->getAttribute('id'), $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createPayment(array $body)
    {
        $method   = $body['method'];
        $customer = $body['customer'];
        $serial   = $body['serial'];
        $planId   = $body['paymentKey'];

        $this->logger->info("Creating a payment using $method, customer {$customer['id']}, serial $serial, planId $planId");

        $customerId = null;
        if (isset($customer['customerId'])) {
            $customerId = $customer['customerId'];
            unset($customer['customerId']);
        }

        $newDateTime = new DateTime();

        $user = new _RegistrationsController($this->em);

        $paymentSuccess = false;

        $profile = $user->updateOrCreate($customer, $serial);
        $profileId = $profile->getId();
        $plan = $this->getPlanById($planId);

        if ($plan === false) {
            $this->logger->error("Plan $planId does not exist");
            return Http::status(404, 'PLAN_NOT_FOUND');
        }

        $doublePaymentCheck = $this->em->createQueryBuilder()
            ->select('u.creationdate')
            ->from(UserPayments::class, 'u')
            ->where('u.creationdate > :now')
            ->andWhere('u.profileId = :id')
            ->andWhere('u.serial = :serial')
            ->setParameter(':now', $newDateTime->modify('-5 minutes'))
            ->setParameter(':id', $profileId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($doublePaymentCheck)) {
            $this->logger->error("Prevented double charge on customer {$customer['id']}, serial $serial, planId $planId");
            $this->mixpanel->track('prevented double charge', [
                'input' => $body
            ]);
            return Http::status(429, 'PREVENTED_POSSIBLE_MULTIPLE_CHARGES');
        }
        $payment = new UserPayments(
            $profile->getEmail(),
            $serial,
            $plan['duration'],
            $plan['cost'],
            $profileId,
            $plan['deviceAllowance'],
            $planId
        );

        if ($method === 'paypal') {
            $payment->transactionId = $body['transaction_id'];
            $paymentSuccess         = true;
        } elseif ($method === 'stripe') {
            if ($user->isEmailValid($profile->getEmail()) === false) {
                $this->logger->info("Validating email for {$profileId} on serial {$serial}");
                $validation = new _ValidationController($this->em);
                $validation->sendValidate($serial, $profileId);
            }

            $pay    = new _StripeChargeController($this->logger, $this->em);
            $charge = $pay->createCharge($serial, $customerId, $plan);

            if ($charge['status'] === 200) {
                $transactionId = $charge['message']['id'];
                $this->logger->info("Stripe success with $transactionId");
                $payment->transactionId = $transactionId;
                $paymentSuccess         = true;
            }
        } elseif ($method === 'manual') {
            if (isset($body['duration'])) {
                $payment->duration = $body['duration'];
            }
            if (isset($body['cost'])) {
                $payment->paymentAmount = $body['cost'];
            }
            if (isset($body['reason'])) {
                $payment->reason = $body['reason'];
            }
            $payment->transactionId = 'manual';
            $paymentSuccess         = true;
        }
        if ($paymentSuccess === true) {
            $this->em->persist($payment);
            $this->em->flush();

            $newPush = new PushNotifications($this->em);

            $toSend = $newPush->getMembersViaSerial($body['serial'], 'capture_payment');

            $notification         = new Models\Notifications\Notification(
                $payment->id,
                'Captured Payment',
                'capture_payment',
                '/' . $serial . '/payments'
            );
            $notification->serial = $serial;
            $this->em->persist($notification);
            $this->em->flush();

            foreach ($toSend as $key => $user) {
                $newPush->pushNotification(
                    $notification,
                    'specific',
                    ['uid' => $user['uid']]
                );
            }

            $this->mixpanel->track(
                'nearly_payment',
                $body
            );


            $this->logger->info("Sending receipt for payment using $method, customer $customerId, serial $serial, planId $planId");

            $mailer = new _ReceiptController($this->em);
            $mailer->send(
                [
                    'transaction_id' => $payment->transactionId,
                    'amount'         => $payment->paymentAmount / 100,
                    'plan_name'      => $plan['name'],
                    'devices'        => $payment->devices,
                    'duration'       => $payment->duration
                ],
                [
                    [
                        'name' => $profile->getFirst() . ' ' . $profile->getLast(),
                        'to'   => $profile->getEmail()
                    ]
                ],
                $serial
            );
            $this->logger->info("Completed payment using $method, customer $customerId, serial $serial, planId $planId");
            return Http::status(200, [
                'customer' => $profile->jsonSerialize()
            ]);
        }

        $this->mixpanel->track('payment_failed', [
            'input' => $body
        ]);
        $this->logger->error("Failed payment using $method, customer $customerId, serial $serial, planId $planId");
        return Http::status(402, 'FAILED_TO_CREATE_PAYMENT');
    }

    public function getPlanById(string $id)
    {
        $getDuration = $this->em->createQueryBuilder()
            ->select('u')
            ->from(Models\LocationPlan::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        if (empty($getDuration)) {
            return false;
        }

        return $getDuration[0];
    }

    public function getAll(Request $request, Response $response)
    {
        $serials = $request->getAttribute('user')['access'];

        $query = $this->em->createQueryBuilder()
            ->select('p')
            ->from(UserPayments::class, 'p')
            ->where('p.serial IN (:serials)')
            ->andWhere('p.profileId = :id')
            ->setParameter('serials', $serials)
            ->setParameter('id', $request->getAttribute('id'))
            ->getQuery()
            ->getArrayResult();

        if (empty($query)) {
            $this->em->clear();

            return $response->withJson(Http::status(204), 204);
        }

        $locations          = [];
        $headingInformation = [
            'payments' => sizeof($query),
            'duration' => 0,
            'amount'   => 0
        ];

        foreach ($query as $key => $payment) {
            $locations[$query[$key]['serial']][] = $query[$key];
            $headingInformation['duration']      += $payment['duration'];
            $headingInformation['amount']        += $payment['paymentAmount'];
        }

        $headingInformation['averagePayment'] = round($headingInformation['amount'] / $headingInformation['payments']);

        $responseMessage = Http::status(200, [
            'locations' => $locations,
            'totals'    => $headingInformation
        ]);

        $this->em->clear();

        return $response->withJson($responseMessage, 200);
    }

    public function get(Request $request, Response $response)
    {

        $serials = $request->getAttribute('user')['access'];
        $id      = $request->getAttribute('paymentId');

        $send = $this->getLogic($id, $serials);

        return $response->withJson($send, 200);
    }

    public function getLogic(int $paymentId, array $serials)
    {
        $res = $this->em->createQueryBuilder()
            ->select('p')
            ->from(UserPayments::class, 'p')
            ->where('p.serial IN (:serials)')
            /** Check through the sites that the logged in user has access to */
            ->andWhere('p.id = :id')
            ->setParameter('serials', $serials)
            ->setParameter('id', $paymentId)
            ->getQuery()
            ->getArrayResult();

        $this->em->clear();

        if (empty($res)) {
            return Http::status(204);
        }

        $response = [
            'id'              => null,
            'cost'            => null,
            'customer'        => [
                'email' => null
            ],
            'deviceAllowance' => null,
            'duration'        => null,
            'method'          => null,
            'paymentKey'      => null,
            'serial'          => null
        ];

        $response['cost']              = $res[0]['paymentAmount'];
        $response['customer']['email'] = $res[0]['email'];
        $response['deviceAllowance']   = $res[0]['devices'];
        $response['duration']          = $res[0]['duration'];
        $response['method']            = $res[0]['transactionId'];
        $response['paymentKey']        = $res[0]['planId'];
        $response['serial']            = $res[0]['serial'];
        $response['id']                = $res[0]['id'];

        return Http::status(200, $response);
    }

    public function updatePayment(int $paymentId, array $body)
    {

        $payment = $this->em->getRepository(UserPayments::class)->findOneBy([
            'id' => $paymentId
        ]);

        $allowedInBody = [
            'cost'            => 'paymentAmount',
            'deviceAllowance' => 'devices',
            'duration'        => 'duration'
        ];

        if (is_null($payment)) {
            return Http::status(404, 'NO_RECORD_OF_PAYMENT');
        }


        foreach ($body as $key => $value) {

            if ($key === 'customer') {
                $payment->email = $value['email'];
            } elseif (isset($allowedInBody[$key])) {
                $payment->{$allowedInBody[$key]} = $value;
            }
        }

        $this->em->flush();

        return Http::status(200, $body);
    }
}
