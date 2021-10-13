<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 10/05/2017
 * Time: 16:26
 */

namespace App\Controllers\Locations\Settings;

use App\Models\EmailAlerts;
use App\Models\FileUploads;
use App\Models\LocationTypes;
use App\Models\NetworkSettings;
use App\Utils\CacheEngine;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationNetworkSettings
{
    protected $em;
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function saveSettingsRoute(Request $request, Response $response)
    {
        $serial  = $request->getAttribute('serial');
        $setting = $request->getAttribute('kind');
        $user    = $request->getAttribute('user');
        $body    = $request->getParsedBody();

        $send = $this->saveSettings($serial, $setting, $body, $user);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSettingsRoute(Request $request, Response $response)
    {
        $serial           = $request->getAttribute('serial');
        $setting          = $request->getAttribute('kind');
        $additionalParams = $request->getQueryParams();
        $allowedTypes     = ['header', 'background'];
        if (isset($additionalParams['type'])) {
            if (in_array($additionalParams['type'], $allowedTypes)) {
                $send = $this->getSettings($serial, $setting, $additionalParams['type']);
            }
        } else {
            $send = $this->getSettings($serial, $setting);
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    private function saveSettings(string $serial, string $setting, array $body, array $user)
    {
        $networkQueryKinds = ['setup', 'capture', 'wifi', 'branding', 'venueType', 'hybrid'];
        $network           = null;

        if (!in_array($setting, $networkQueryKinds)) {
            return Http::status(409, 'INVALID_SETTING');
        }

        $network = $this->em->getRepository(NetworkSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($network)) {
            return Http::status(404, 'FAILED_TO_LOCATE_NETWORK');
        }

        switch ($setting) {
            case 'setup':
                $requiredParams = ['language', 'type', 'url', 'alias', 'location'];
                $validate       = Validation::pastRouteBodyCheck($body, $requiredParams);

                if (is_array($validate)) {
                    return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
                }

                if (!isset($body['translation'])) {
                    $body['translation'] = false;
                }


                $network->translation = $body['translation'];
                $network->language    = $body['language'];
                $network->type        = $body['type'];
                $network->url         = $body['url'];
                $network->alias       = $body['alias'];
                $network->location    = $body['location'];


                $this->em->flush();

                break;
            case 'capture':
                $requiredParams = ['freeQuestions', 'facebook', 'other', 'customQuestions'];
                $validate       = Validation::pastRouteBodyCheck($body, $requiredParams);

                if (is_array($validate)) {
                    return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
                }
                $freeQuestions   = $body['freeQuestions'];
                $facebook        = $body['facebook'];
                $other           = $body['other'];
                $customQuestions = $body['customQuestions'];


                $network->freeQuestions   = $freeQuestions;
                $network->other           = $other;
                $network->facebook        = $facebook;
                $network->customQuestions = $customQuestions;


                $this->em->flush();
                break;
            case 'hybrid':
                $requiredParams = ['other'];
                $validate       = Validation::pastRouteBodyCheck($body, $requiredParams);

                if (is_array($validate)) {
                    return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
                }
                $network->other = $body['other'];


                $this->em->flush();
                break;
            case 'locationType':
                $requiredParams = ['locationType'];
                $validate       = Validation::pastRouteBodyCheck($body, $requiredParams);

                if (is_array($validate)) {
                    return Http::status(400, 'REQUIRES' . '_' . strtoupper(implode('_', $validate)));
                }
                $locationKind = $this->em->getRepository(LocationTypes::class)->findOneBy([
                    'name' => $body['locationType']
                ]);


                if (is_object($locationKind)) {
                    $network->locationType = $locationKind->id;
                    $this->em->flush();
                    break;
                }


                break;
        }

        return Http::status(200, $network->getArrayCopy());
    }

    private function getSettings(string $serial, string $setting, string $imageType = null)
    {
        $data = null;
        switch ($setting) {
            case 'alerts':
                $alert = $this->em->getRepository(EmailAlerts::class)->findOneBy([
                    'serial' => $serial
                ]);

                if (is_object($alert)) {
                    $data['types']   = $alert->types;
                    $data['list']    = $alert->list;
                    $data['enabled'] = $alert->enabled;
                } else {
                    $data['types']   = false;
                    $data['list']    = false;
                    $data['enabled'] = false;
                }

                return Http::status(200, $data);
                break;
            case 'logo':
                $image = $this->em->createQueryBuilder()
                    ->select('f.url')
                    ->from(FileUploads::class, 'f')
                    ->where("f.kind = :kind")
                    ->andWhere('f.path LIKE :serial')
                    ->andWhere('f.deleted = 0')
                    ->setParameter('kind', 'brandingLogo' . $imageType)
                    ->setParameter('serial', '%' . $serial . '%')
                    ->getQuery()
                    ->getArrayResult();
                if (!empty($image)) {
                    return Http::status(200, $image[0]);
                }
                break;
        }

        return Http::status(404, 'COULD_NOT_RETRIEVE_' . strtoupper($setting) . '_FOR_' . $serial);
    }
}
