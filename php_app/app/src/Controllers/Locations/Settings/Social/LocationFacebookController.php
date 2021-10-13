<?php
/**
 * Created by jamieaitken on 07/02/2018 at 16:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Social;

use App\Controllers\Integrations\Mikrotik\_MikrotikFacebookController;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationFacebookController
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

    public function get(string $serial)
    {
        $socialId = $this->getSocialIdViaSerial($serial);

        $social = $this->em->getRepository(LocationSocial::class)->findOneBy([
            'id' => $socialId
        ]);

        return Http::status(200, $social->getArrayCopy());
    }

    public function update(string $serial, array $body)
    {

        $socialId = $this->getSocialIdViaSerial($serial);

        $social = $this->em->getRepository(LocationSocial::class)->findOneBy([
            'id' => $socialId
        ]);

        $keys = array_keys($social->getArrayCopy());

        foreach ($body as $key => $value) {
            if (!in_array($key, $this->immutableKeys) && in_array($key, $keys)) {
                $social->$key = $value;
            }
        }

        $this->em->flush();

        if ($body['enabled'] === true) {
            $locationSettings = new _LocationSettingsController($this->em);
            $vendor           = $locationSettings->getVendor($serial);

            if (strtolower($vendor) === 'mikrotik') {
                $newMikroTikFacebookConfig = new _MikrotikFacebookController($this->em);
                $newMikroTikFacebookConfig->setFacebook($body, $serial);
            }
            /*
            switch (strtolower($vendor)) {
                case 'unifi':
                    break;
                case 'mikrotik':
                    $newMikroTikFacebookConfig = new _MikrotikFacebookController($this->em, $this->firebase);
                    $newMikroTikFacebookConfig->setFacebook($facebook, $serial);
                    break;
                case 'openmesh':
                    break;
            }
            */
        }

        $sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        $sitesTemplate = [$serial . ':social'];

        foreach ($sitesUsingThisAsTemplate as $key => $site) {
            $sitesTemplate[] = $site['serial'] . ':social';
        }

        $this->nearlyCache->deleteMultiple($sitesTemplate);


        return Http::status(200, $social->getArrayCopy());
    }

    public function getSocialIdViaSerial(string $serial)
    {
        return $this->em->createQueryBuilder()
                   ->select('u.facebook')
                   ->from(LocationSettings::class, 'u')
                   ->where('u.serial = :s')
                   ->setParameter('s', $serial)
                   ->getQuery()
                   ->getArrayResult()[0]['facebook'];
    }

    public function getNearlySocial(string $serial, string $socialId)
    {
        $exists = $this->nearlyCache->fetch($serial . ':social');
        if (!is_bool($exists)) {
            return Http::status(200, $exists);
        }

        $social = $this->em->createQueryBuilder()
                      ->select('u.enabled, u.page')
                      ->from(LocationSocial::class, 'u')
                      ->where('u.id = :i')
                      ->setParameter('i', $socialId)
                      ->getQuery()
                      ->getArrayResult()[0];

        $this->nearlyCache->save($serial . ':social', $social);

        return Http::status(200, $social);
    }
}