<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 19/04/2017
 * Time: 17:45
 */

namespace App\Controllers\Integrations\UniFi;


use App\Models\Integrations\UniFi\UnifiController;
use App\Models\Integrations\UniFi\UnifiLocation;
use App\Models\UnifiC;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class _UniFiSettingsController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $serial
     * @return mixed
     **/

    public function settings(string $serial)
    {

        $results = $this->em->createQueryBuilder()
            ->select('u.id, u.hostname, u.username, u.password, u.lastRequest, u.version, s.unifiId, s.timeout, s.multiSite, s.multiSiteSsid')
            ->from(UnifiController::class, 'u')
            ->leftJoin(UnifiLocation::class, 's', 'WITH', 'u.id = s.unifiControllerId')
            ->where('s.serial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();


        if (!empty($results)) {
            return Http::status(200, $results[0]);
        }

        return Http::status(404, 'NO_SETTINGS_FOUND');
    }
}
