<?php
/**
 * Created by jamieaitken on 07/08/2018 at 17:28
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Google;

use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Reviews\LocationReviews;
use Curl\Curl;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class PlaceIDVerifierController
{
    protected $em;
    private $baseEndPoint = 'https://maps.googleapis.com/maps/api/place/details/json';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('placeId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $placeId)
    {

        $request  = new Curl();
        $response = $request->get($this->baseEndPoint, [
            'key'     => getenv('GOOGLE_MAPS_API_KEY'),
            'placeid' => $placeId,
            'fields'  => 'formatted_address,name'
        ]);

        if ($response->status !== 'OK') {
            return Http::status(400);
        }

        return Http::status(200, $response->result);
    }
}