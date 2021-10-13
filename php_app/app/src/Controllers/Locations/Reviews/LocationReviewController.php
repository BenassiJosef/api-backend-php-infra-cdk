<?php
/**
 * Created by jamieaitken on 30/07/2018 at 16:50
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reviews;

use App\Models\Locations\Reviews\LocationReviews;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationReviewController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createReviewTypeRoute(Request $request, Response $response)
    {

        $send = $this->createReviewType($request->getParsedBody(), $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getReviewTypesRoute(Request $request, Response $response)
    {

        $send = $this->getReviewTypes($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createReviewType(array $body, string $serial)
    {
        if (!isset($body['reviewType'])) {
            return Http::status(400, 'REVIEW_TYPE_MISSING');
        }

        if (!isset($body['enabled'])) {
            return Http::status(400, 'ENABLED_KEY_MISSING');
        }

        if (!isset($body['bias']) && $body['hasBias'] === true) {
            return Http::status(409, 'NO_BIAS_KEY');
        }

        $allowedTypes = ['google', 'facebook', 'tripadvisor', 'blackbx'];

        if (!in_array($body['reviewType'], $allowedTypes)) {
            return Http::status(409, 'INVALID_REVIEW_TYPE');
        }

        $searchCriteria = [
            'serial' => $serial
        ];

        if (is_null($body['id'])) {
            $searchCriteria['reviewType'] = $body['reviewType'];
        } else {
            $searchCriteria['id'] = $body['id'];
        }

        $doesTypeExist = $this->em->getRepository(LocationReviews::class)->findOneBy($searchCriteria);

        if (is_null($doesTypeExist) && is_null($body['id'])) {
            $doesTypeExist = new LocationReviews($serial, $body['reviewType']);
            $this->em->persist($doesTypeExist);
        }


        if (!isset($body['bias']) && $body['hasBias'] === true) {
            $getReviewIntegrations = $this->em->createQueryBuilder()
                ->select('u.id, u.bias')
                ->from(LocationReviews::class, 'u')
                ->where('u.serial = :ser')
                ->andWhere('u.id != :idGettingUpdated')
                ->andWhere('u.reviewType != :blackbx')
                ->setParameter('ser', $serial)
                ->setParameter('idGettingUpdated', $body['id'])
                ->setParameter('blackbx', 'blackbx')
                ->getQuery()
                ->getArrayResult();

            if (sizeof($getReviewIntegrations) > 0) {
                $totalBias = $body['bias'];

                foreach ($getReviewIntegrations as $key => $reviewIntegration) {

                    $totalBias += $reviewIntegration['bias'];

                    if ($totalBias > 100) {
                        return Http::status(409, 'HAVE_LOWER_BIAS');
                    }
                }
            }

            $doesTypeExist->hasBias = $body['hasBias'];
            $doesTypeExist->bias    = $body['bias'];
        }

        $doesTypeExist->enabled = $body['enabled'];

        $this->em->flush();

        $response            = $doesTypeExist->getArrayCopy();
        $response['enabled'] = $body['enabled'];
        $response['hasBias'] = $body['hasBias'];
        $response['bias']    = $body['bias'];


        return Http::status(200, $response);
    }

    public function getReviewTypes(string $serial)
    {
        $getTypes = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationReviews::class, 'u')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($getTypes)) {
            $newType              = new LocationReviews($serial, 'blackbx');
            $facebook             = new LocationReviews($serial, 'facebook');
            $facebook->enabled    = false;
            $tripadvisor          = new LocationReviews($serial, 'tripadvisor');
            $tripadvisor->enabled = false;
            $google               = new LocationReviews($serial, 'google');
            $google->enabled      = false;
            $this->em->persist($newType);
            $this->em->persist($facebook);
            $this->em->persist($tripadvisor);
            $this->em->persist($google);
            $this->em->flush();

            $response = array_merge($newType->getArrayCopy(), $facebook->getArrayCopy(), $tripadvisor->getArrayCopy(), $google->getArrayCopy());

            return Http::status(200, $response);
        }

        return Http::status(200, $getTypes);
    }

}