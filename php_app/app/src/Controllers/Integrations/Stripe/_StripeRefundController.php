<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/05/2017
 * Time: 11:10
 */

namespace App\Controllers\Integrations\Stripe;

use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;
use Stripe\Refund;

class _StripeRefundController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRefundRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $refund = $this->createRefund($body);

        $this->em->clear();

        return $response->withJson($refund, $refund['status']);
    }

    private function createRefund(array $body)
    {

        $required = ['reason', 'charge'];

        $validationCheck = Validation::pastRouteBodyCheck($body, $required);
        if (is_array($validationCheck)) {
            return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validationCheck)));
        }


        $alreadyBeenRefunded = $this->em->getRepository(Refunds::class)->findOneBy([
            'chargeId' => $body['charge']
        ]);

        if (is_object($alreadyBeenRefunded)) {
            if ($alreadyBeenRefunded->successful === true) {
                return Http::status(409, 'ALREADY_BEEN_REFUNDED');
            }
        }

        $refund = Refund::create([
            'charge' => $body['charge']
        ]);
    }
}
