<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/08/2017
 * Time: 19:14
 */

namespace App\Controllers\Nearly;

use App\Models\Radius\RadiusCheck;
use App\Models\Radius\RadiusGroupCheck;
use App\Models\Radius\RadiusUserGroup;
use App\Utils\CacheEngine;
use App\Utils\RadiusEngine;

class _NearlyAuthenticationController
{

    protected $cacheBase = 'RadiusCheck:';

    /**
     * _NearlyAuthenticationController constructor.
     * @param $radius
     */

    protected $radius;
    protected $infrastructureCache;

    public function __construct()
    {
        $this->radius = RadiusEngine::getInstance();
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function createOrFind(string $serial, int $profileId)
    {
        $user = $this
            ->radius
            ->getRepository(RadiusCheck::class)->findOneBy([
            'username' => $profileId . $serial,
            'password' => $profileId,
        ]);
        if (is_null($user)) {
            $user = new RadiusCheck($profileId . $serial, $profileId);
            $this->radius->persist($user);

            $userGroup = new RadiusUserGroup($profileId . $serial, $serial);
            $this->radius->persist($userGroup);
        }

        $this->radius->flush();
        return $user->getArrayCopy();
    }

    public function createNasIdentity($serial)
    {
        $rad = $this->infrastructureCache->fetch($this->cacheBase . $serial);
        if ($rad !== false) {
            return $rad;
        }
        $rad = $this->radius->getRepository(RadiusUserGroup::class)->findOneBy([
            'groupname' => $serial,
        ]);
        if (is_null($rad)) {
            $rad = new RadiusGroupCheck($serial, $serial, 'NAS-Identifier');
            $this->radius->persist($rad);
        }

        return $rad->getArrayCopy();
    }
}
