<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/01/2017
 * Time: 18:06
 */

namespace App\Controllers\Locations\Settings;

use App\Controllers\Integrations\Mikrotik\MikrotikSymlinkController;
use App\Models\LandingRequest;

use App\Models\Locations\LocationSettings;
use App\Package\Vendors\Information;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationSettingsController
{

    protected $em;
    protected $nearlyCache;
    protected $information;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
        $this->information = new Information($this->em);
    }

    public function getLandingPageRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');


        $cacheUrl = $this->nearlyCache->fetch($serial . ':landingPage');

        if (is_bool($cacheUrl)) {
            $send = $this->getLandingPage($serial);
            $this->nearlyCache->save($serial . ':landingPage', $send);
        } else {
            $send = $cacheUrl;
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getLandingPage(string $serial = '')
    {

        $symlink   = new MikrotikSymlinkController($this->em);
        $symSerial = $symlink->getVirtualSerialAssociatedWithPhysicalSerial($serial);

        if ($symSerial['status'] === 200) {
            $serial = $symSerial['message'];
        }

        $select = $this->em->createQueryBuilder()
            ->select('l.url')
            ->from(LocationSettings::class, 'l')
            ->where('l.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($select)) {
            return Http::status(404, 'URL_NOT_FOUND');
        }

        $log = new LandingRequest($serial, $select[0]['url']);

        $this->em->persist($log);
        $this->em->flush();

        return Http::status(200, $select[0]['url']);
    }

    public function getVendor(string $serial)
    {

        $inform = $this->information->getFromSerial($serial);
        if (is_null($inform)) {
            return false;
        }

        return $inform->getVendorSource()->getKey();
    }

    public function getType(string $serial)
    {
        $results = $this->em->createQueryBuilder()
            ->select('i.type')
            ->from(LocationSettings::class, 'i')
            ->where('i.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($results)) {
            return false;
        }

        return (int)$results[0]['type'];
    }
}
