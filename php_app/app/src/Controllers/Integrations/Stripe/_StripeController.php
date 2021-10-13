<?php

namespace App\Controllers\Integrations\Stripe;

use App\Controllers\Branding\_BrandingController;
use App\Models\Locations\LocationSettings;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\StripeConnect;
use App\Models\StripeCustomer;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Stripe\Account;
use Stripe\Error;
use Stripe\Stripe;

/** WALLEDGARDEN REQUIRED FOR CONTROLLER TO FUNCTION
 *  checkout.stripe.com, js.stripe.com, stripecdn.com, api.stripe.com
 */
class _StripeController
{

    protected $em;

    public $stripe_user_id;
    protected $logger;

    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;
    /**
     * @var OrganizationService
     */
    private $organisationService;

    /**
     * _StripeController constructor.
     * @param EntityManager $em
     */

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->orgIdProvider = new OrganisationIdProvider($this->em);
        $this->organisationService = new OrganizationService($this->em);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return static
     */

    public function authorize(Request $request, Response $response)
    {

        $orgId = $request->getAttribute('orgId');

        $stripeUrl = 'https://connect.stripe.com/oauth/authorize?';

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'ca_8Vp6I2NlzXgMq18Mt30MdJzPadLyVB4T',
            'scope' => 'read_write',
            'state' => $orgId
        ];

        $this->em->clear();

        return $response->withStatus(200)->write(
            json_encode(['location' => $stripeUrl . http_build_query($queryParams)])
        );
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return static
     */

    public function get(Request $request, Response $response)
    {

        $orgId = $request->getAttribute('orgId');
        $send = $this->getAllByID($orgId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deauthorizeAccountRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $send = $this->deauthorizeStripeAccount($orgId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deauthorizeStripeAccount($orgId)
    {

        $account = $this->em->getRepository(StripeConnect::class)->findOneBy([
            'organizationId' => $orgId
        ]);
        if (is_object($account)) {
            $deAuthURL = 'https://connect.stripe.com/oauth/deauthorize';
            $curl = new Curl();
            $paramsToSend = [
                'client_id' => Stripe::getAccountId(),
                'stripe_user_id' => $account->stripe_user_id
            ];

            $curl->post($deAuthURL, $paramsToSend);
            if ($curl->error) {
                return Http::status($curl->errorCode, $curl->errorMessage);
            }
            $response = $curl->response;
            if ($response->stripe_user_id == $account->stripe_user_id) {
                $account->isDeleted = 1;
                $this->em->persist($account);
                $this->em->flush();

                return Http::status(200, 'SUCCESSFULLY_DEAUTHORIZED');
            }

            return Http::status($response['error'], $response['error_description']);
        }

        return Http::status(404, 'COULD_NOT_LOCATE_STRIPE_ACCOUNT');
    }

    /**
     * @param string $code
     * @param string $type
     * @param string $organizationId
     * @return array|bool
     */

    private function stripeOAuth(string $code, string $type, string $organizationId)
    {
        $oauthURL = 'https://connect.stripe.com/oauth/token';
        $curl = new Curl();
        $paramsToSend = [
            'client_secret' => getenv('stripe_key'),
            'grant_type' => $type
        ];
        if ($type == 'refresh_token') {
            $paramsToSend['refresh_token'] = $code;
        } elseif ($type == 'authorization_code') {
            $paramsToSend['code'] = $code;
        }

        $curl->post($oauthURL, $paramsToSend);
        if ($curl->error) {
            $data = [
                'code' => $curl->errorCode,
                'message' => $curl->errorMessage,
                'reason' => $curl->response
            ];

            return $data;
        } else {
            $decoded = $curl->response;
            $connection = $this->em->getRepository(StripeConnect::class)->findOneBy(
                [
                    'stripe_user_id' => $decoded->stripe_user_id
                ]
            );

            if (is_null($connection)) {
                $organisation = $this->em->getRepository(Organization::class)->find($organizationId);
                $connection = new StripeConnect(
                    $organisation,
                    $decoded->token_type,
                    $decoded->stripe_user_id,
                    $decoded->stripe_publishable_key,
                    $decoded->scope,
                    $decoded->livemode,
                    $decoded->refresh_token,
                    $decoded->access_token
                );
            } else {
                $connection->token_type = $decoded->token_type;
                $connection->stripe_publishable_key = $decoded->stripe_publishable_key;
                $connection->scope = $decoded->scope;
                $connection->livemode = $decoded->livemode;
                $connection->refresh_token = $decoded->refresh_token;
                $connection->access_token = $decoded->access_token;
                $connection->organizationId = $organizationId;
            }

            self::init(null);

            $stripeAccount = Account::retrieve($connection->stripe_user_id);
            $connection->display_name = $stripeAccount->display_name;

            $this->em->persist($connection);
            $this->em->flush();

            return true;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return static
     */

    public function callback(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        $organizationId = $params['state'];
        if ($params['code'] != null) {
            $callToStripe = $this->stripeOAuth($params['code'], 'authorization_code', $organizationId);
            if ($callToStripe === true) {

                return $response->withStatus(302)
                    ->withHeader('Location', 'https://product.stampede.ai/gifting');
            } else {
                $response->withStatus($callToStripe['code']);

                return $response->write(json_encode($callToStripe));
            }
        } else {
            $data = [
                'error' => 'An unknown error has occurred'
            ];
            if ($params['error_description'] != null) {
                $data['error'] = $params['error_description'];
            }

            return $response->withJson($data, 400);
        }
    }

    /**
     * @param string $uid
     * @return array|bool
     */

    public function getAllByID(string $orgId)
    {
        $stripeAccounts = $this->em->getRepository(StripeConnect::class)->findBy([
            'organizationId' => $orgId
        ]);

        if ($stripeAccounts === null) {
            return Http::status(400, 'COULD_NOT_GET_BY_ID');
        }

        $returnArray = [];
        foreach ($stripeAccounts as $account) {
            $returnArray[] = $account->getArrayCopy();
        }

        return Http::status(200, $returnArray);
    }

    /**
     * @param string $profileId
     * @return bool
     */

    public function getStripeCustomerIdFromProfileID(string $profileId)
    {
        $checkForID = $this->em->createQueryBuilder()
            ->select('u')
            ->from(StripeCustomer::class, 'u')
            ->where('u.profileId = :id')
            ->setParameter('id', $profileId)
            ->getQuery()
            ->getArrayResult();

        if (!empty($checkForID)) {
            return $checkForID[0];
        }

        return false;
    }

    /**
     * @param string $serial
     * @return bool
     */

    public function getStripeAccountFromSerial(string $serial)
    {
        $getIdBasedOnSerial = $this->em->createQueryBuilder()
            ->select('u.stripe_connect_id')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :e')
            ->setParameter('e', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($getIdBasedOnSerial)) {
            return $getIdBasedOnSerial[0]['stripe_connect_id'];
        }

        return false;
    }

    public function getStripeCustomerIdFromUid(string $uid)
    {
        $qb = $this->em->createQueryBuilder();
        $getCustomerIdFromUid = $qb->select('u.stripe_id')
            ->from(OauthUser::class, 'u')
            ->where('u.uid = :id')// TODO OrgId replace
            ->setParameter('id', $uid)
            ->getQuery()
            ->getArrayResult();
        if (!empty($getCustomerIdFromUid)) {
            return $getCustomerIdFromUid[0]['stripe_id'];
        }

        return false;
    }

    public function getStripeUserIdFromStripeCustomerId($stripeCustomerId = '')
    {
        $getStripeUserId = $this->em->createQueryBuilder()
            ->select('u.stripe_user_id')
            ->from(StripeCustomer::class, 'u')
            ->where('u.stripeCustomerId = :customer')
            ->setParameter('customer', $stripeCustomerId)
            ->getQuery()
            ->getArrayResult();
        if (!empty($getStripeUserId)) {
            return $getStripeUserId[0]['stripe_user_id'];
        }

        return false;
    }

    /**
     * @param null $stripe_account_id
     */

    public function init($stripe_account_id = null)
    {
        Stripe::setApiKey(getenv('stripe_key'));
        if ($stripe_account_id) {
            Stripe::setAccountId($stripe_account_id);
        }
    }

    public function handleErrors($req, $args, $args1 = '')
    {

        if (is_null(Stripe::getApiKey())) {
            $this->init(null);
        }
        try {
            if (!empty($args1)) {
                $res = $req($args1, $args);
            } else {
                $res = $req($args);
            }
        } catch (Error\Permission $e) {
            $this->logger->error("Stripe failed (Permission) {$e->getMessage()}");

            // Since it's a decline, \Stripe\Error\Card will be caught
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\Card $e) {
            $this->logger->error("Stripe failed (Card) {$e->getMessage()}");

            // Since it's a decline, \Stripe\Error\Card will be caught
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\RateLimit $e) {
            $this->logger->error("Stripe failed (RateLimit) {$e->getMessage()}");

            // Too many requests made to the API too quickly
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\InvalidRequest $e) {
            $this->logger->error("Stripe failed (InvalidRequest) {$e->getMessage()}");

            // Invalid parameters were supplied to Stripe's API
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\Authentication $e) {
            $this->logger->error("Stripe failed (Authentication) {$e->getMessage()}");
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\ApiConnection $e) {
            $this->logger->error("Stripe failed (ApiConnection) {$e->getMessage()}");

            // Network communication with Stripe failed
            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\Api $e) {
            $this->logger->error("Stripe failed (Api) {$e->getMessage()}");

            return Http::status($e->getHttpStatus(), $e->getJsonBody());
        } catch (Error\Base $e) {
            $this->logger->error("Stripe failed (Base) {$e->getMessage()}");
            // Display a very generic error to the user, and maybe send
            // yourself an email
            return Http::status($e->getHttpStatus(), 'SOMETHING_WENT_WRONG');
        } catch (\Exception $e) {
            $this->logger->error("Stripe failed with generic exception {$e->getMessage()}");

            // Something else happened, completely unrelated to Stripe
            return Http::status(500);
        }

        return Http::status(200, $res);
    }
}
