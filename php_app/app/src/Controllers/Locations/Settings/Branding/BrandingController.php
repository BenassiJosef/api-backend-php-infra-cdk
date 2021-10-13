<?php

/**
 * Created by jamieaitken on 06/02/2018 at 09:45
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Branding;

use App\Controllers\Integrations\CloudFront\CloudFront;
use App\Controllers\Integrations\S3\S3;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Templating\LocationTemplate;
use App\Utils\CacheEngine;
use App\Utils\DominantImageColor;
use App\Utils\Http;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class BrandingController
{
    protected $em;
    protected $nearlyCache;
    protected $s3;
    private $immutableKeys = [
        'id',
        'customCSS',
        'updatedAt'
    ];
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
        $this->s3 = new S3('', '');
    }

    public function createFromTemplateRoute(Request $request, Response $response)
    {
        $send = $this->createFromTemplate($request->getParsedBody(), $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {

        $send = $this->get($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $send = $this->deleteImage($request->getAttribute('serial'), $request->getAttribute('type'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getColorsRoute(Request $request, Response $response)
    {
        $params = $request->getQueryParams();

        if (!isset($params['url'])) {
            return $response->withJson(Http::status(400, 'NO_URL'), 400);
        }

        $send = $this->getColors($params['url']);

        return $response->withJson($send, $send['status']);
    }

    public function createFromTemplate(array $body, string $serial)
    {
        if (!array_key_exists('serial', $body)) {
            return Http::status(409, 'SERIAL_OF_BASE_MUST_BE_IN_BODY');
        }

        $getBaseBrandingId = $this->getBrandingIdFromSerial($body['serial']);

        $getCurrentSite = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        $getCurrentSite->branding = $getBaseBrandingId;

        $this->em->flush();

        $this->nearlyCache->delete($serial . ':branding');


        /**
         * NEEDS REFACTORED */

        $currentSiteArray = $this->getNearlyBranding($serial, $getBaseBrandingId)['message'];

        $updates = [
            'branding' => $currentSiteArray
        ];

        return Http::status(200, $currentSiteArray);
    }

    public function get(string $serial)
    {
        /**
         * @var LocationSettings $location
         */
        $location = $this->em->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);
        $branding = $location->getBrandingSettings();

        if (is_null($branding)) {
            return Http::status(404, 'COULD_NOT_LOCATE_BRANDING');
        }

        if (!is_null($branding->getCustomCSS()) && !empty($branding->getCustomCSS())) {
            $branding->setCustomCSS(file_get_contents($branding->getCustomCSS()));
        }

        return Http::status(200, $branding->jsonSerialize());
    }

    public function update(string $serial, array $body)
    {
        /**
         * @var LocationSettings $location
         */
        $location = $this->em->getRepository(LocationSettings::class)->findOneBy(['serial' => $serial]);
        if (is_null($location)) {
            return Http::status(404, 'COULD_NOT_FIND_LOCATION');
        }
        $branding = $location->getBrandingSettings();


        if (is_null($branding)) {
            return Http::status(404, 'COULD_NOT_LOCATE_CURRENT_BRANDING');
        }

        foreach ($body as $key => $value) {
            if ($key === 'id') {
                continue;
            };
            $branding->{$this::camelCase($key)} = $value;
        }
        $branding->updatedAt = new DateTime();

        if (!is_null($branding->getCustomCSS()) && !empty($branding->getCustomCSS())) {
            $this->s3->removeFile('/locations/' . $serial . '/branding/css/custom.css');


            $cssFileContents = $branding->getCustomCSS();
            $cssFile         = fopen('cssFile.css', 'w');
            fwrite($cssFile, $cssFileContents);
            fclose($cssFile);
            $upload                     = $this->s3->upload(
                '/locations/' . $serial . '/branding/css/custom.css',
                'string',
                'cssFile.css',
                'public-read',
                [
                    'CacheControl' => 'max-age=31536000',
                    'ContentType'  => 'text/css'
                ]
            );
            $branding->setCustomCSS($upload);
            unlink('cssFile.css');

            $nearlyS3 = new S3('nearly.online', 'eu-west-1');
            $nearlyS3->removeFile('static/media/branding/' . $serial . '/css/custom.css');

            $invalidation = new CloudFront();
            $invalidation->invalidate($serial);

            $branding->setCustomCSS(file_get_contents($branding->getCustomCss()));
        }
        $this->nearlyCache->delete($serial . ':branding');

        $sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($sitesUsingThisAsTemplate)) {
            $sitesTemplate = [];

            foreach ($sitesUsingThisAsTemplate as $key => $site) {
                $sitesTemplate[] = $site['serial'] . ':branding';
            }

            $this->nearlyCache->deleteMultiple($sitesTemplate);
        }


        $this->em->persist($branding);
        $this->em->flush();
        return Http::status(200, $branding->jsonSerialize());
    }

    public function deleteImage(string $serial, string $type)
    {
        $allowedTypes = [
            'bg',
            'head'
        ];

        if (!in_array($type, $allowedTypes)) {
            return Http::status(409, 'INVALID_TYPE');
        }

        $brandingId = $this->getBrandingIdFromSerial($serial);

        $branding = $this->em->getRepository(LocationBranding::class)->findOneBy([
            'id' => $brandingId
        ]);

        $s3 = new S3('nearly.online', 'eu-west-1');

        if ($type === 'bg') {
            $locationBackgroundFile = '/static/media/branding/' . substr(
                $branding->backgroundImage,
                strpos(
                    $branding->backgroundImage,
                    'location/'
                ) + 9
            );
            $s3->removeFile($locationBackgroundFile);
            $branding->backgroundImage = null;
        } elseif ($type === 'head') {
            $locationHeaderFile = '/static/media/branding/' . substr(
                $branding->headerImage,
                strpos(
                    $branding->headerImage,
                    'location/'
                ) + 9
            );
            $s3->removeFile($locationHeaderFile);
            $branding->headerImage = null;
        }

        $this->em->flush();

        $this->nearlyCache->delete($serial . ':branding');

        $sitesUsingThisAsTemplate = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationTemplate::class, 'u')
            ->where('u.serialCopyingFrom = :ser')
            ->setParameter('ser', $serial)
            ->getQuery()
            ->getArrayResult();

        $sitesTemplate = [];

        foreach ($sitesUsingThisAsTemplate as $key => $site) {
            $sitesTemplate[] = $site['serial'] . ':branding';
        }

        $this->nearlyCache->deleteMultiple($sitesTemplate);

        return Http::status(200);
    }

    public function getColors(string $url)
    {
        try {

            $dominant = new DominantImageColor($url);

            return Http::status(200, $dominant->getPalette());
        } catch (\RuntimeException $error) {
            return Http::status(204);
        }
    }

    private function getBrandingIdFromSerial(string $serial)
    {
        return $this->em->createQueryBuilder()
            ->select('u.branding')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult()[0]['branding'];
    }





    public static function defaultBranding(): LocationBranding
    {
        $default                 = new LocationBranding(
            LocationBranding::defaultBackground(),
            LocationBranding::defaultBoxShadow(),
            LocationBranding::defaultFooter(),
            LocationBranding::defaultHeaderLogoPadding(),
            LocationBranding::defaultHeaderTopRadius(),
            LocationBranding::defaultHeaderColor(),
            LocationBranding::defaultInput(),
            LocationBranding::defaultBackgroundImage(),
            LocationBranding::defaultHeaderImage(),
            LocationBranding::defaultPrimary(),
            LocationBranding::defaultRoundFormTopLeft(),
            LocationBranding::defaultRoundFormTopRight(),
            LocationBranding::defaultRoundFormBottomLeft(),
            LocationBranding::defaultRoundFormBottomRight(),
            LocationBranding::defaultTextColor(),
            LocationBranding::defaultRoundInputs(),
            LocationBranding::defaultHideFooter()
        );
        $default->interfaceColor = LocationBranding::defaultInterfaceColor();

        return $default;
    }

    private static function camelCase(string $str)
    {
        $i = array("-", "_");
        $str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
        $str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
        $str = str_replace($i, ' ', $str);
        $str = str_replace(' ', '', ucwords(strtolower($str)));
        $str = strtolower(substr($str, 0, 1)) . substr($str, 1);
        return $str;
    }
}
