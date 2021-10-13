<?php

namespace App\Controllers\Integrations\IgniteNet;

use App\Controllers\Clients\_ClientsController;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 28/03/2017
 * Time: 11:22
 */
class _IgniteNetController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function updateClientDataRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return $response->withJson(Http::status(400, 'NEEDS_TO_BE_AN_ARRAY'));
        }
        $send = $this->updateClientData($request->getAttribute('serial'), $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    private function updateClientData(string $serial, array $body)
    {
        $client         = new _ClientsController($this->em);
        $updatedClients = 0;
        foreach ($body as $key => $value) {
            if (is_array($value)) {
                $client->update($value['download'], $value['upload'], $value['mac'], $serial, $value['ip']);
                $updatedClients++;
            }
        }

        if ($updatedClients === sizeof($body)) {
            return Http::status(200, 'ALL_CLIENTS_UPDATED');
        } elseif ($updatedClients > 0 && $updatedClients < sizeof($body)) {
            return Http::status(206, $updatedClients . '_CLIENTS_UPDATED');
        } else {
            return Http::status(400, 'FAILED_TO_UPDATE');
        }
    }
}
