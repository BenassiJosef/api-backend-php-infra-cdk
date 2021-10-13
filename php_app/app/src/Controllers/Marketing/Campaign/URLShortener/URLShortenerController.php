<?php
/**
 * Created by jamieaitken on 15/04/2018 at 10:41
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Marketing\Campaign\URLShortener;

use App\Models\Marketing\ShortUrl;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class URLShortenerController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {
        $send = $this->create($request->getParsedBody(), $request->getAttribute('uid'));

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->getAllShortLinks($request->getAttribute('uid'));

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        return $response->withJson($send, $send['status']);
    }

    public function create(array $body, string $uid)
    {
        $responseStructure = [
            'links' => []
        ];


        foreach ($body as $key => $link) {
            $newLink = new ShortUrl($link, ShortUrl::generate(), $uid);
            $this->em->persist($newLink);
            $responseStructure['links'][] = $newLink->getArrayCopy();
        }


        $this->em->flush();

        return Http::status(200, $responseStructure);
    }



    public function update()
    {
        return Http::status(200);
    }

    public function delete()
    {
        return Http::status(200);
    }
}