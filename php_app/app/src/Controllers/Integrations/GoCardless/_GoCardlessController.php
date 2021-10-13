<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 18/07/2017
 * Time: 16:30
 */

namespace App\Controllers\Integrations\GoCardless;

use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeePaymentSourceController;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;
use GoCardlessPro\Client;
use GoCardlessPro\Environment;
use Slim\Http\Response;
use Slim\Http\Request;

class _GoCardlessController
{

    protected $infrastructureCache;
    protected $em;
    protected $client;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
        $this->client              = new Client([
            // You'll need to identify the user that the customer is paying and fetch their
            // access token
            'access_token' => getenv('GC_ACCESS_TOKEN'),
            // Change me to LIVE when you're ready to go live
            'environment'  => Environment::LIVE
        ]);
    }

    public function getLinkRoute(Request $request, Response $response)
    {

        $user = $request->getAttribute('accessUser');

        $finalRedirect = $request->getQueryParams()['redirect_url'];

        $link = $this->generateLink($user, 'https://api.blackbx.io/go-cardless/callback', $finalRedirect);

        $this->em->clear();

        return $response->withJson([
            'status' => 200,
            'link'   => $link
        ], 200);
    }

    public function callbackRoute(Request $request, Response $response)
    {

        $params = $request->getQueryParams();

        $complete = $this->completeFlow($params['redirect_flow_id']);

        if ($complete['status'] === 200) {
            return $response->withHeader('Location', $complete['message']);
        }

        $this->em->clear();

        return $response->withJson($complete, $complete['status']);
    }

    public function generateLink(array $user, string $redirectUri, string $finalRedirect)
    {
        $sessionToken          = 'gc:' . Strings::random(12);
        $user['session_id']    = $sessionToken;
        $user['finalRedirect'] = $finalRedirect;

        $redirectFlow = $this->client->redirectFlows()->create([
            'params' => [
                'session_token'        => $sessionToken,
                'success_redirect_url' => $redirectUri,
                'prefilled_customer'   => [
                    'given_name'  => $user['first'],
                    'family_name' => $user['last'],
                    'email'       => $user['email']
                ]
            ]
        ]);

        $this->infrastructureCache->save('goCardlessRedirects:' . $redirectFlow->id, $user);

        return $redirectFlow->redirect_url;
    }

    public function completeFlow(string $flowId)
    {
        $user = $this->infrastructureCache->fetch('goCardlessRedirects:' . $flowId);

        if ($user === false) {
            return Http::status(400, 'USER_NOT_FOUND');
        }

        $redirectFlow = $this->client->redirectFlows()->complete(
            $flowId,
            [
                'params' => [
                    'session_token' => $user['session_id']
                ]
            ]
        );

        $newPaymentController = new _ChargeBeePaymentSourceController($this->em);
        $addDD                = $newPaymentController->addDirectDebitPaymentSource($user['uid'],
            $redirectFlow->links->mandate);
        if ($addDD['status'] === 200) {
            $newCustomerController = new _ChargeBeeCustomerController($this->em);
            $newCustomerController->changePrimaryPayment([
                'uid'       => $user['uid'],
                'paymentId' => $addDD['message']['id']
            ]);

            return Http::status(200, $user['finalRedirect']);
        }

        return Http::status($addDD['status'], $addDD['message']);
    }
}
