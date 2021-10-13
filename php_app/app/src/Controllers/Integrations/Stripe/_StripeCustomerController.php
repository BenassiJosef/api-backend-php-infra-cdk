<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 05/01/2017
 * Time: 19:25
 */

namespace App\Controllers\Integrations\Stripe;

use App\Controllers\Registrations\_RegistrationsController;
use App\Models\StripeCustomer;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Stripe\Customer;

class _StripeCustomerController extends _StripeController
{

    protected $em;

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em = $em;
        parent::__construct($logger, $em);
    }

    //Route Functions

    public function postCustomerRoute(Request $request, Response $response)
    {
        $params = $request->getParsedBody();
        if (isset($params['id'])) {
            $send              = $this->createCustomer($params['id'], $params['serial'], null);
            $send['profileId'] = $send['id'];
        } elseif (isset($params['customer'])) {
            $newCustomer       = new _RegistrationsController($this->em);
            $customer          = $newCustomer->updateOrCreate($params['customer'], $params['serial']);
            $send              = $this->createCustomer($customer->getId(), $params['serial'], $params['token']);
            $send['profileId'] = $customer->getId();
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateCustomerRoute(Request $request, Response $response)
    {
        $body       = $request->getParsedBody();
        $customerId = $request->getAttribute('id');

        if (isset($body['_method'])) {
            unset($body['_method']);
        }

        $send = $this->updateCustomer($customerId, $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function retrieveCustomerRoute(Request $request, Response $response)
    {
        $send = $this->retrieveCustomer($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteCustomerRoute(Request $request, Response $response)
    {
        $send = $this->deleteCustomer($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    //End Routes

    /**
     * @param int $id
     * @param string $serial
     * @param null $token
     * @return array
     */

    public function createCustomer(int $id = 0, string $serial = '', $token = null)
    {

        $registrationController = new _RegistrationsController($this->em);
        $profile                = $registrationController->getProfile($id, '');

        if (empty($profile)) {
            return Http::status(400, 'USER_PROFILE_NOT_FOUND');
        }

        $customer = [
            'email'       => $profile['email'],
            'description' => $profile['first'] . ' ' . $profile['last'],
            'metadata'    => [
                'profileId' => $id
            ]
        ];

        if (!is_null($token)) {
            $customer['source'] = $token;
        }

        $stripe_user_id = parent::getStripeAccountFromSerial($serial);

        if (empty($stripe_user_id)) {
            return Http::status(400, 'LOCATION_ACCOUNT_CODE_INVALID');
        }

        $getStripeCustomer = $this->checkCustomerForStripeId($stripe_user_id, $id);

        if (!empty($getStripeCustomer) && $getStripeCustomer !== false) {
            return Http::status(200, $getStripeCustomer);
        }

        $stripeCustomer = $this->createStripeCustomer($stripe_user_id, $customer);

        if ($stripeCustomer['status'] !== 200) {
            return Http::status($stripeCustomer['status'], $stripeCustomer['message']);
        }

        $newDateTime = new \DateTime();

        $doublePaymentCheck = $this->em->createQueryBuilder()
            ->select('u.created')
            ->from(StripeCustomer::class, 'u')
            ->where('u.created > :now')
            ->andWhere('u.profileId = :id')
            ->andWhere('u.stripeCustomerId = :serial')
            ->setParameter(':now', $newDateTime->modify('-5 minutes'))
            ->setParameter(':id', $profile['id'])
            ->setParameter('serial', $stripeCustomer['message']['id'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($doublePaymentCheck)) {
            return Http::status(429, 'PREVENTED_POSSIBLE_MULTIPLE_CHARGES');
        }

        $newCustomer = new StripeCustomer(
            $id,
            $stripeCustomer['message']['id'],
            $stripe_user_id
        );

        $this->em->persist($newCustomer);
        $this->em->flush();

        return Http::status($stripeCustomer['status'], $newCustomer->getArrayCopy());
    }

    public function createStripeCustomer($stripe_user_id = null, array $args = [])
    {
        parent::init($stripe_user_id);

        $stripeCustomerCreation = function ($customer) {
            return Customer::create($customer);
        };

        return parent::handleErrors($stripeCustomerCreation, $args);
    }

    public function updateStripeCustomer($stripe_user_id = null, string $customerId = '', array $args = [])
    {
        parent::init($stripe_user_id);

        $stripeCustomerCreation = function ($customerId, $args) {
            return Customer::update($customerId, $args);
        };

        return parent::handleErrors($stripeCustomerCreation, $args, $customerId);
    }

    public function retrieveCustomerFromStripe($stripe_user_id = null, string $customerId = '')
    {
        parent::init($stripe_user_id);

        $stripeCustomer = function ($customerId) {
            return Customer::retrieve($customerId);
        };

        return parent::handleErrors($stripeCustomer, $customerId);
    }

    public function retrieveCustomer(string $id = '')
    {
        $registrationController = new _RegistrationsController($this->em);
        $profile                = $registrationController->getProfile($id, '');
        if (empty($profile)) {
            return Http::status(400, 'USER_PROFILE_NOT_FOUND');
        }
        $stripe_customer = parent::getStripeCustomerIdFromProfileID($id);
        if (empty($stripe_customer)) {
            return Http::status(400, 'LOCATION_ACCOUNT_CODE_INVALID');
        }
        $retrieve = $this->retrieveCustomerFromStripe(
            $stripe_customer['stripe_user_id'],
            $stripe_customer['stripeCustomerId']
        );
        if ($retrieve['status'] !== 200) {
            return Http::status($retrieve['status'], $retrieve['message']);
        }

        return Http::status($retrieve['status'], $retrieve['message']);
    }

    /**
     * @param string $id
     * @param array $metadata
     * @return array|\Exception|object|\Stripe\Error\ApiConnection|\Stripe\Error\Authentication|\Stripe\Error\Base|\Stripe\Error\Card|\Stripe\Error\InvalidRequest|\Stripe\Error\Permission|\Stripe\Error\RateLimit
     */

    private function updateCustomer(string $id = '', array $metadata = [])
    {
        $stripe_customer = $this->getStripeAccountFromCustomerId($id);

        if (empty($stripe_customer)) {
            return Http::status(400, 'LOCATION_ACCOUNT_CODE_INVALID');
        }

        $updateCustomer = $this->updateStripeCustomer(
            $stripe_customer['stripe_user_id'],
            $stripe_customer['stripeCustomerId'],
            $metadata
        );

        if ($updateCustomer['status'] !== 200) {
            return Http::status($updateCustomer['status'], $updateCustomer['message']);
        }

        return Http::status($updateCustomer['status'], $updateCustomer['message']);
    }

    /**
     * @param string $id
     * @return array
     */

    private function deleteCustomer(string $id = '')
    {
        $isStripeCustomer = $this->getStripeAccountFromCustomerId($id);
        if (empty($isStripeCustomer)) {
            return Http::status(400, 'LOCATION_ACCOUNT_CODE_INVALID');
        }
        $removeCustomer = $this->deleteCustomerFromStripe(
            $isStripeCustomer['stripe_user_id'],
            $isStripeCustomer['stripeCustomerId']
        );

        if ($removeCustomer['status'] !== 200) {
            return Http::status($removeCustomer['status'], $removeCustomer['message']);
        }

        return Http::status($removeCustomer['status'], $removeCustomer['message']);
    }


    private function deleteCustomerFromStripe($stripe_user_id = null, string $customerId = '')
    {
        parent::init($stripe_user_id);
        $stripeCustomerDeletion = function ($customerId) {
            $customer = Customer::retrieve($customerId);

            return $customer->delete();
        };

        return parent::handleErrors($stripeCustomerDeletion, $customerId);
    }

    /**
     * @param string $customerId
     * @return bool
     */

    public function getStripeAccountFromCustomerId(string $customerId = '')
    {

        $checkforID = $this->em->createQueryBuilder()
            ->select('u')
            ->from(StripeCustomer::class, 'u')
            ->where('u.stripeCustomerId = :stripeCustomerId')
            ->setParameter('stripeCustomerId', $customerId)
            ->getQuery()
            ->getArrayResult();

        if (!empty($checkforID)) {
            return $checkforID[0];
        }

        return false;
    }

    /**
     * @param null $stripe_user_id
     * @param string $profileId
     * @return bool
     */

    public function getStripeCustomerIdFromAccountCodeAndProfileId($stripe_user_id = null, string $profileId = '')
    {
        $checkforID = $this->em->createQueryBuilder()
            ->select('u')
            ->from(StripeCustomer::class, 'u')
            ->where('u.profileId = :profileId')
            ->setParameter('profileId', $profileId)
            ->andWhere('u.stripe_user_id = :profileId')
            ->setParameter('profileId', $stripe_user_id)
            ->getQuery()
            ->getArrayResult();

        if (!empty($checkforID)) {
            return $checkforID[0]['stripeCustomerId'];
        }

        return false;
    }

    /**
     * @param string $stripe_user_id
     * @param string $id
     * @return bool
     */

    private function checkCustomerForStripeId(string $stripe_user_id = '', string $id = '')
    {
        $customer = $this->em->createQueryBuilder()
            ->select('u.profileId, u.stripe_user_id, u.stripeCustomerId')
            ->from(StripeCustomer::class, 'u')
            ->where('u.profileId = :id')
            ->andWhere('u.stripe_user_id = :stripe_user_id')
            ->setParameter('id', $id)
            ->setParameter('stripe_user_id', $stripe_user_id)
            ->getQuery()
            ->getArrayResult();

        if (!empty($customer)) {
            return $customer[0];
        }

        return false;
    }
}
