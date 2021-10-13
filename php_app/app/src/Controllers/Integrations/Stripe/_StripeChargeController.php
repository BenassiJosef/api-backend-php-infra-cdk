<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 06/01/2017
 * Time: 11:47
 */

namespace App\Controllers\Integrations\Stripe;


use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Stripe\Charge;

class _StripeChargeController
{
    protected $stripeCustomer;
    protected $mixpanel;
    protected $logger;

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->stripeCustomer = new _StripeCustomerController($logger, $em);
        $this->mixpanel       = new _Mixpanel();
        $this->logger = $logger;
    }

    //Route Functions
    public function createChargeRoute(Request $request, Response $response)
    {
        $params = $request->getParsedBody();

        $send = $this->createCharge($params['serial'], $params['id'], $params['plan']);

        return $response->withJson($send, $send['status']);
    }

    public function retrieveChargeRoute(Request $request, Response $response)
    {
        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($request->getAttribute('customerId'));
        $send          = $this->retrieveChargeInStripe($stripeAccount['stripe_user_id'],
            $request->getAttribute('chargeId'));

        return $response->withJson($send, $send['status']);
    }

    public function updateChargeRoute(Request $request, Response $response)
    {
        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($request->getAttribute('customerId'));
        $body          = $request->getParsedBody();
        $send          = $this->updateChargeInStripe($stripeAccount['stripe_user_id'],
            $request->getAttribute('chargeId'), $body);

        return $response->withJson($send, $send['status']);
    }

    public function retrieveAllChargesRoute(Request $request, Response $response)
    {
        $customer      = $request->getAttribute('customerId');
        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($customer);
        $send          = $this->retrieveAllCharges($stripeAccount['stripe_user_id'], $customer);

        return $response->withJson($send, $send['status']);
    }

    //End Routes

    public function createCharge(string $serial, string $customerId, array $plan)
    {
        $this->logger->info("Creating charge for customer $customerId, on serial $serial");
        $stripe_account = $this->stripeCustomer->getStripeAccountFromSerial($serial);
        if (empty($stripe_account)) {
            $this->logger->error("Could not find a stripe account for $serial");
            return Http::status(400, 'LOCATION_ACCOUNT_CODE_INVALID');
        }

        $charge = $this->createStripeCharge(
            $stripe_account,
            $customerId,
            [
                'amount'                => $plan['cost'],
                'plan_id'               => $plan['id'],
                'plan_name'             => $plan['name'],
                'plan_device_allowance' => $plan['deviceAllowance'],
                'plan_duration'         => $plan['duration'],
                'serial'                => $serial
            ]
        );

        $this->logger->info("Created a charge with status {$charge['status']} message {$charge['message']}");
        return Http::status($charge['status'], $charge['message']);
    }

    public function createStripeCharge($stripe_user_id = null, string $customerId, array $metadata)
    {
        $this->stripeCustomer->init($stripe_user_id);

        $charge = function ($customerId, $metadata) {
            $amount = $metadata['amount'];
            unset($metadata['amount']);


            return Charge::create([
                'amount'          => $amount,
                'application_fee' => ceil($amount * 0.005),
                'currency'        => 'gbp',
                'customer'        => $customerId,
                'description'     => $metadata['plan_name'],
                'metadata'        => $metadata
            ]);
        };

        try {
            $response = $this->stripeCustomer->handleErrors($charge, $metadata, $customerId);

            if ($response['status'] !== 200) {
                $this->logger->error("Stripe Charge failed");
                $this->mixpanel->track('STRIPE_FAILED_TO_CREATE_CHARGE', [
                    'input'  => $metadata,
                    'output' => $response
                ]);
            }

            return $response;
        } catch (\ArgumentCountError $e) {
            $this->logger->error("Strip charge failed {$e->getMessage()}");
            $mp = new _Mixpanel();
            $mp->track('TOO_FEW_ARGS_FOR_STRIPE_CHARGE', [
                'charge'       => $charge,
                'metadata'     => $metadata,
                'stripeUserId' => $stripe_user_id,
                'customerId'   => $customerId
            ]);
        }

    }

    public function retrieveAllCharges($stripe_user_id = null, string $customerId)
    {
        $this->stripeCustomer->init($stripe_user_id);
        $charges = function ($customerId) {
            return Charge::all([
                'customer' => $customerId
            ]);
        };

        return $this->stripeCustomer->handleErrors($charges, $customerId);
    }

    public function retrieveChargeInStripe($stripe_user_id = null, string $chargeId)
    {
        $this->stripeCustomer->init($stripe_user_id);
        $charge = function ($chargeID) {
            return Charge::retrieve($chargeID);
        };

        return $this->stripeCustomer->handleErrors($charge, $chargeId);
    }

    public function updateChargeInStripe($stripe_user_id = null, string $chargeId, array $args)
    {
        $this->stripeCustomer->init($stripe_user_id);
        $charge = function ($chargeId, $args) {
            $charge    = Charge::retrieve($chargeId);
            $canChange = ['description', 'metadata', 'receipt_email', 'fraud_details', 'shipping'];
            foreach ($args as $key => $value) {
                if (in_array($key, $canChange)) {
                    $charge->$key = $value;
                }
            }

            return $charge->save();
        };

        return $this->stripeCustomer->handleErrors($charge, $chargeId, $args);
    }
}
