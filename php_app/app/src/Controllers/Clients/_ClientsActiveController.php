<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 28/06/2017
 * Time: 15:08
 */

namespace App\Controllers\Clients;

use App\Models\UserData;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class _ClientsActiveController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function activeClients(string $serial)
    {
        $now   = new \DateTime();
        $check = $this->em->createQueryBuilder()
            ->select('u')
            ->from(UserData::class, 'u')
            ->where('u.serial = :serial')
            ->andWhere('u.timestamp > :now OR u.lastupdate > :now')
            ->setParameter(':now', $now->modify('-10 minutes'))
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (!empty($check)) {
            return Http::status(200, $check);
        }

        return Http::status(404, 'NO_ACTIVE_CLIENTS');
    }
}
