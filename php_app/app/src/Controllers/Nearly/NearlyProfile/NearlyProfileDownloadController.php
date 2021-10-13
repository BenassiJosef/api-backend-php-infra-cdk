<?php
/**
 * Created by jamieaitken on 03/05/2018 at 11:53
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile;

use App\Controllers\Nearly\NearlyProfileController;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyProfileDownloadController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {
        $send = $this->create($request->getAttribute('nearlyUser')['profileId'], [], $request->getAttribute('type'));

        return $send['message']['engine']->download('', $send['message']['contents'], $response,
            $request->getAttribute('nearlyUser')['profileId']);
    }

    public function create(string $userId, array $serials, string $type)
    {
        $type = strtolower($type);

        $allowedTypes = ['csv', 'pdf'];

        if (!in_array($type, $allowedTypes)) {
            return Http::status(409, 'INCOMPATIBLE_TYPE');
        }

        $fetchProfile = new NearlyProfileController($this->em);
        $profile      = $fetchProfile->loadProfile($userId, $serials);

        if ($profile['status'] !== 200) {
            return Http::status(404, 'INVALID_USER');
        }

        $profile = $profile['message'];

        switch ($type) {
            case 'csv':
                $exportEngine = new NearlyProfileCSV($userId, $serials);
                $export       = $exportEngine->create($profile);
                break;
            case 'pdf':
                $exportEngine = new NearlyProfilePDF($userId, $serials);
                $export       = $exportEngine->create($profile);
                break;
        }

        return Http::status(200, ['engine' => $exportEngine, 'contents' => $export]);
    }
}