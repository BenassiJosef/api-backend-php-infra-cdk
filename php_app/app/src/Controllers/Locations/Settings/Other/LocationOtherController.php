<?php

/**
 * Created by jamieaitken on 07/02/2018 at 15:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Other;

use App\Controllers\Integrations\DDNS\DDNS;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationOtherController
{
    protected $em;
    protected $nearlyCache;
    private $immutableKeys = ['id', 'updatedAt'];

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $send = $this->update($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function update(string $serial, array $body)
    {
        $locationId = $this->getOtherIdLocationBySerial($serial);

        $getLocation = $this->em->getRepository(LocationOther::class)->findOneBy([
            'id' => $locationId
        ]);

        $keys = array_keys($getLocation->getArrayCopy());

        foreach ($body as $key => $value) {
            if (!in_array($key, $this->immutableKeys) && in_array($key, $keys)) {
                $getLocation->$key = $value;
            }
        }

        if ($getLocation->ddnsEnabled) {
            $ddns = new DDNS('', $serial);
            $ddns->create();
        }

        if (!$getLocation->ddnsEnabled) {
            $ddns = new DDNS('', $serial);
            $ddns->delete();
        }

        $this->em->flush();

        $sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        $sitesTemplate = [$serial . ':other'];

        foreach ($sitesUsingThisAsTemplate as $key => $site) {
            $sitesTemplate[] = $site['serial'] . ':other';
        }

        $this->nearlyCache->deleteMultiple($sitesTemplate);

        return Http::status(200, $getLocation->getArrayCopy());
    }

    public function get(string $serial)
    {
        $locationId = $this->getOtherIdLocationBySerial($serial);

        $location = $this->em->getRepository(LocationOther::class)->findOneBy([
            'id' => $locationId
        ]);

        if (is_null($location)) {
            return Http::status(404, 'COULD_NOT_LOCATE_ANY_OTHER_INFORMATION');
        }

        return Http::status(200, $location->getArrayCopy());
    }

    public function getOtherIdLocationBySerial(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.other')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult()[0]['other'];
    }

    public function getNearlyOther(string $serial, string $otherId)
    {
        $exists = $this->nearlyCache->fetch($serial . ':other');
        if (!is_bool($exists)) {
            return Http::status(200, $exists);
        }

        $other = $this->em->createQueryBuilder()
            ->select('   u.hybridLimit, 
                                         u.optText, 
                                         u.validation, 
                                         u.validationTimeout, 
                                         u.optChecked, 
                                         u.optRequired,
                                         u.allowSpamEmails,
                                         u.onlyBusinessEmails,
                                         u.ddnsEnabled,
                                         u.smsVerification, 
                                         u.appleSignIn')
            ->from(LocationOther::class, 'u')
            ->where('u.id = :n')
            ->setParameter('n', $otherId)
            ->getQuery()
            ->getArrayResult()[0];

        $this->nearlyCache->save($serial . ':other', $other);

        return Http::status(200, $other);
    }

    public static function defaultOther(): LocationOther
    {
        $other                     = new LocationOther(
            LocationOther::defaultValidation(),
            LocationOther::defaultHybridLimit(),
            LocationOther::defaultOptText()
        );
        $other->allowSpamEmails    = LocationOther::defaultAllowSpamEmails();
        $other->onlyBusinessEmails = LocationOther::defaultOnlyBusinessEmails();
        $other->ddnsEnabled        = LocationOther::defaultDDNSEnabled();
        return $other;
    }
}
