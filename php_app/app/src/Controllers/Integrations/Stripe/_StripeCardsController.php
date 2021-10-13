<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 05/01/2017
 * Time: 19:25
 */

namespace App\Controllers\Integrations\Stripe;

use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class _StripeCardsController
{
    protected $stripeCustomer;
    private $logger;

    /**
     * _StripeCardsController constructor.
     * @param Logger $logger
     * @param EntityManager $em
     */

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->logger = $logger;
        $this->stripeCustomer = new _StripeCustomerController($logger, $em);
    }

    public function addCard(Request $request, Response $response)
    {

        $params     = $request->getParsedBody();
        $card       = $params['card'];
        $customerId = $params['customerId'];

        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($customerId);

        $send = $this->stripeAddCard($stripeAccount['stripe_user_id'], $customerId, $card);

        return $response->withJson($send, $send['status']);
    }

    public function getCards(Request $request, Response $response)
    {

        $customerId = $request->getAttribute('customerId');

        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($customerId);
        if (empty($stripeAccount)) {
            return $response->withStatus(404)->write(
                json_encode(Http::status(404, 'INVALID_CUSTOMER_ID'))
            );
        }
        $send = $this->stripeListCards($stripeAccount['stripe_user_id'], $customerId);
        return $response->withJson($send, $send['status']);
    }

    public function deleteCard(Request $request, Response $response)
    {
        $cardId     = $request->getAttribute('cardId');
        $customerId = $request->getAttribute('customerId');

        $stripeAccount = $this->stripeCustomer->getStripeAccountFromCustomerId($customerId);
        if (empty($stripeAccount)) {
            return $response->withStatus(404)->write(
                json_encode(Http::status(404, 'INVALID_CUSTOMER_ID'))
            );
        }
        $send = $this->stripeDeleteCard($stripeAccount['stripe_user_id'], $customerId, $cardId);

        return $response->withJson($send, $send['status']);
    }

    public function stripeAddCard($stripe_user_id = null, $customerId = '', $card = [])
    {
        $this->stripeCustomer->init($stripe_user_id);
        $customerCard = function ($card, $customerId) {
            $customer = $this->stripeCustomer->retrieveCustomerFromStripe(null, $customerId);
            if ($customer['status'] === 200) {
                return $customer['message']->sources->create(
                    [
                        'source' => $card
                    ]
                );
            }

            return $customer;
        };

        return $this->stripeCustomer->handleErrors($customerCard, $customerId, $card);
    }

    public function stripeDeleteCard($stripe_user_id = null, $customerId = '', $cardId = '')
    {
        $this->stripeCustomer->init($stripe_user_id);
        $deleteCard = function ($cardId, $customerId) {
            $customer = $this->stripeCustomer->retrieveCustomerFromStripe(null, $customerId);
            if ($customer['status'] === 200) {
                return $customer['message']->sources->retrieve($cardId)->delete();
            }

            return $customer;
        };

        return $this->stripeCustomer->handleErrors($deleteCard, $customerId, $cardId);
    }

    public function stripeListCards($stripe_user_id = null, $customerId = '')
    {
        $this->stripeCustomer->init($stripe_user_id);
        $listCards = function ($customerId) {
            $customer = $this->stripeCustomer->retrieveCustomerFromStripe(null, $customerId);
            if ($customer['status'] === 200) {
                $cards = $customer['message']->sources->all([
                    'limit'  => 10,
                    'object' => 'card'
                ]);

                $cards->default_source = $customer['message']->default_source;

                return $cards;
            }

            return $customer;
        };

        return $this->stripeCustomer->handleErrors($listCards, $customerId);
    }

    public function editCard($stripe_user_id = null, $update = [])
    {
        $this->stripeCustomer->init($stripe_user_id);
        $updateCard = function ($update) {
            $customerId = $update['customerId'];
            $cardId     = $update['cardId'];
            unset($update['customerId']);
            unset($update['cardId']);
            $customer = $this->stripeCustomer->retrieveCustomerFromStripe(null, $customerId);
            if ($customer['status'] === 200) {
                $card      = $customer['message']->sources->retrieve($cardId);
                $canChange = [
                    'address_city', 'address_country', 'address_line1', 'address_line2', 'address_state', 'address_zip',
                    'exp_month', 'exp_year', 'metadata', 'name'
                ];
                foreach ($update as $key => $value) {
                    if (in_array($key, $canChange)) {
                        $card->$key = $value;
                    }
                }

                return $card->save();
            }

            return $customer;
        };

        return $this->stripeCustomer->handleErrors($updateCard, $update);
    }
}
