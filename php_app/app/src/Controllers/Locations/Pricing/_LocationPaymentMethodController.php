<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 24/01/2017
 * Time: 09:48
 */

namespace App\Controllers\Locations\Pricing;

use App\Models\Locations\LocationSettings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationPaymentMethodController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getMethodsRoute(Request $request, Response $response)
    {
        $send = $this->getPaymentMethods($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateMethodRoute(Request $request, Response $response)
    {

        $body = $request->getParsedBody();
        $send = $this->updateMethod($request->getAttribute('serial'), $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateMethod(string $serial = '', array $methodObj = [])
    {
        $canChange = ['stripe_connect_id', 'paypalAccount', 'paymentType'];

        $data = $this->em->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);

        if (is_null($data)) {
            return Http::status(400, 'FAILED_TO_UPDATE_PAYMENT_METHOD');
        }

        $response = $methodObj;

        $paymentType = '';
        if ($methodObj['paypal']['enabled'] === true) {
            $paymentType = 'paypal';
        }
        if ($methodObj['stripe']['enabled'] === true) {
            $paymentType .= ' stripe';
        }
        unset($methodObj['paypal']['enabled']);
        unset($methodObj['stripe']['enabled']);

        $data->paymentType = trim($paymentType);

        foreach ($methodObj as $key => $item) {
            foreach ($methodObj[$key] as $k => $i) {
                if (in_array($k, $canChange)) {
                    $data->$k = $i;
                }
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        return Http::status(200, $response);
    }

    public function getPaymentMethods(string $serial = '')
    {
        $data = $this->em->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);

        if (is_null($data)) {
            return Http::status(400, 'COULD_NOT_LOCATE_SERIAL');
        }

        $userPaymentMethods = [
            'paypal' => [
                'enabled'       => false,
                'paypalAccount' => $data->paypalAccount
            ],
            'stripe' => [
                'enabled'           => false,
                'stripe_connect_id' => $data->stripe_connect_id
            ]
        ];
        if (strpos($data->paymentType, 'paypal') !== false) {
            $userPaymentMethods['paypal']['enabled'] = true;
        }
        if (strpos($data->paymentType, 'stripe') !== false) {
            $userPaymentMethods['stripe']['enabled'] = true;
        }

        return Http::status(200, $userPaymentMethods);
    }
}
