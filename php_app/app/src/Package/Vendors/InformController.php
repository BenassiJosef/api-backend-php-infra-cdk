<?php

namespace App\Package\Vendors;

use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class InformController
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;


    /**
     * @var Information $information
     */
    private $information;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager

    ) {
        $this->entityManager = $entityManager;
        $this->information = new Information($this->entityManager);
    }

    public function getInform(Request $request, Response $response): Response
    {
        $serial = $request->getAttribute('serial', null);
        if (is_null($serial)) {
            return $response->withJson('NO_SERIAL_IN_REQUEST', 403);
        }
        if (strlen($serial) !== 12) {
            return $response->withJson('WRONG_SERIAL_FORMAT', 403);
        }

        $inform = $this->information->getFromSerial($serial);

        if (is_null($inform)) {
            return $response->withJson('SERIAL_NOT_FOUND', 403);
        }
        return $response->withJson($inform->jsonSerialize());
    }
}
