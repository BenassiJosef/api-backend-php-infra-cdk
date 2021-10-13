<?php
/**
 * Created by jamieaitken on 24/07/2018 at 10:38
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Templating;

use App\Models\Locations\LocationSettings;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationTemplateController
{
    protected $em;
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function createTemplateRoute(Request $request, Response $response)
    {

        $send = $this->createTemplate($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getTemplateRoute(Request $request, Response $response)
    {
        $send = $this->getTemplate($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateTemplateRoute(Request $request, Response $response)
    {
        $send = $this->updateTemplate($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function restoreDefaultRoute(Request $request, Response $response)
    {
        $send = $this->restoreDefaults($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createTemplate(string $serial, array $body)
    {
        if (!isset($body['serialCopyingFrom'])) {
            return Http::status(400, 'NO_SERIAL');
        }

        $keysTheUserWants = [];

        if (isset($body['branding'])) {
            if ($body['branding']) {
                array_push($keysTheUserWants, 'branding');
            }
        }

        if (isset($body['other'])) {
            if ($body['other']) {
                array_push($keysTheUserWants, 'other');
            }
        }

        if (isset($body['url'])) {
            if ($body['url']) {
                array_push($keysTheUserWants, 'url');
            }
        }

        if (isset($body['wifi'])) {
            if ($body['wifi']) {
                array_push($keysTheUserWants, 'wifi');
            }
        }

        if (isset($body['location'])) {
            if ($body['location']) {
                array_push($keysTheUserWants, 'location');
            }
        }

        if (isset($body['social'])) {
            if ($body['social']) {
                array_push($keysTheUserWants, 'social');
            }
        }

        if (isset($body['type'])) {
            if ($body['type']) {
                array_push($keysTheUserWants, 'type');
            }
        }

        if (isset($body['customQuestions'])) {
            if ($body['customQuestions']) {
                array_push($keysTheUserWants, 'customQuestions');
            }
        }

        if (isset($body['freeQuestions'])) {
            if ($body['freeQuestions']) {
                array_push($keysTheUserWants, 'freeQuestions');
            }
        }

        if (isset($body['schedule'])) {
            if ($body['schedule']) {
                array_push($keysTheUserWants, 'schedule');
            }
        }

        if (isset($body['translation'])) {
            if ($body['translation']) {
                array_push($keysTheUserWants, 'translation', 'language');
            }
        }

        if (isset($body['payment'])) {
            if ($body['payment']) {
                array_push($keysTheUserWants, 'stripe_connect_id', 'paymentType', 'paypalAccount');
            }
        }

        $copyingFrom = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $body['serialCopyingFrom']
        ]);

        $gettingOverridden = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        $arrayCopyOfBaseInformation       = $copyingFrom->getArrayCopy();
        $arrayCopyOfOverriddenInformation = $gettingOverridden->getArrayCopy();

        $template = new LocationTemplate();
        foreach ($arrayCopyOfOverriddenInformation as $key => $information) {
            if (in_array($key, $keysTheUserWants)) {
                $template->$key = $information;
            }
        }

        $template->serial            = $serial;
        $template->serialCopyingFrom = $body['serialCopyingFrom'];
        if (in_array('freeQuestions', $keysTheUserWants)) {
            $template->freeQuestions = $gettingOverridden->freeQuestions;
        }

        if (in_array('customQuestions', $keysTheUserWants)) {
            if (!empty($gettingOverridden->customQuestions) && !is_null($gettingOverridden->customQuestions)) {
                $template->customQuestions = $gettingOverridden->customQuestions;
            }
        }

        foreach ($arrayCopyOfBaseInformation as $key => $information) {
            if (in_array($key, LocationSettings::$keys) && in_array($key, $keysTheUserWants)) {
                if ($key !== 'id' && $key !== 'serialCopyingFrom' && !is_null($information) && !empty($information)) {
                    $gettingOverridden->$key = $information;
                }
            }
        }

        $gettingOverridden->freeQuestions = $copyingFrom->freeQuestions;

        if (!is_null($copyingFrom->customQuestions) && !empty($copyingFrom->customQuestions)) {
            $gettingOverridden->customQuestions = $copyingFrom->customQuestions;
        }


        $this->em->persist($template);

        $this->em->flush();

        $this->nearlyCache->deleteMultiple([
                $serial . ':branding',
                $serial . ':location',
                $serial . ':other',
                $serial . ':schedule',
                $serial . ':social'
            ]
        );

        return $this->getTemplate($serial);
    }

    public function isMasterTemplate(string $serial)
    {
        $isMaster = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($isMaster)) {
            return false;
        }

        return true;
    }

    public function getTemplate(string $serial)
    {
        $hasTemplate = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serial = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($hasTemplate)) {
            if ($this->isMasterTemplate($serial)) {
                return Http::status(200, ['master_template' => true, 'serial' => $serial]);
            }

            return Http::status(204);
        }

        foreach ($hasTemplate[0] as $key => $templateItem) {
            if ($key === 'id' || $key === 'serial' || $key === 'serialCopyingFrom') {
                continue;
            }
            if (is_null($templateItem)) {
                $hasTemplate[0][$key] = false;
                continue;
            }
            if (empty($templateItem)) {
                $hasTemplate[0][$key] = false;
                continue;
            }
            $hasTemplate[0][$key] = true;
        }

        return Http::status(200, $hasTemplate[0]);
    }

    public function updateTemplate(string $serial, array $body)
    {
        $restore = $this->restoreDefaults($serial);
        if ($restore['status'] !== 200) {
            return Http::status($restore['status'], $restore['message']);
        }

        $request = $this->createTemplate($serial, $body);

        return Http::status($request['status'], $request['message']);
    }

    public function restoreDefaults(string $serial)
    {

        $backedUpValues = $this->em->getRepository(LocationTemplate::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($backedUpValues)) {
            return Http::status(204);
        }

        $update = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($update)) {
            return Http::status(204);
        }

        $restoreFreeQuestions   = false;
        $restoreCustomQuestions = false;

        if (!is_null($backedUpValues->freeQuestions) || !empty($backedUpValues->freeQuestions)) {
            $restoreFreeQuestions = true;
        }

        if (!is_null($backedUpValues->customQuestions) || !empty($backedUpValues->customQuestions)) {
            $restoreCustomQuestions = true;
        }

        foreach ($backedUpValues->getArrayCopy() as $key => $information) {

            if (!in_array($key, LocationSettings::$keys)) {
                continue;
            }

            if ($key !== 'id' && !is_null($information) && !empty($information)) {
                $update->$key = $information;
            }
        }

        if ($restoreFreeQuestions) {
            $update->freeQuestions = $backedUpValues->freeQuestions;
        }

        if ($restoreCustomQuestions) {
            $update->customQuestions = $backedUpValues->customQuestions;
        }

        $this->em->remove($backedUpValues);
        $this->em->flush();

        $this->nearlyCache->deleteMultiple([
                $serial . ':branding',
                $serial . ':location',
                $serial . ':other',
                $serial . ':schedule',
                $serial . ':social'
            ]
        );

        return Http::status(200);
    }
}