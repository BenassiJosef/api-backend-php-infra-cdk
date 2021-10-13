<?php

/**
 * Created by jamieaitken on 06/02/2019 at 09:47
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Mikrotik;

use App\Models\Locations\Informs\MikrotikSymlinkSerial;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class MikrotikSymlinkController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getVirtualSerialAssociatedWithPhysicalSerialRoute(Request $request, Response $response)
    {

        $send = $this->getVirtualSerialAssociatedWithPhysicalSerial($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function updateThePhysicalSerialAssociatedWithVirtualSerialRoute(Request $request, Response $response)
    {

        $send = $this->updateThePhysicalSerialAssociatedWithVirtualSerial(
            $request->getAttribute('serial'),
            $request->getParsedBody()
        );

        return $response->withJson($send, $send['status']);
    }

    public function getVirtualSerialAssociatedWithPhysicalSerial(string $serial)
    {
        $getVirtualSerial = $this->em->createQueryBuilder()
            ->select('u.virtualSerial')
            ->from(MikrotikSymlinkSerial::class, 'u')
            ->where('u.physicalSerial = :serial')
            ->setParameter('serial', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($getVirtualSerial)) {
            return Http::status(200, $serial);
        }

        return Http::status(200, $getVirtualSerial[0]['virtualSerial']);
    }

    public function updateThePhysicalSerialAssociatedWithVirtualSerial(string $serial, array $body)
    {
        if (!isset($body['serial'])) {
            return Http::status(409, 'PHYSICAL_SERIAL_NOT_SET');
        }

        $isPhysicalSerialBeingUsedElsewhere = $this->em->createQueryBuilder()
            ->select('u.virtualSerial')
            ->from(MikrotikSymlinkSerial::class, 'u')
            ->where('u.physicalSerial = :serial')
            ->setParameter('serial', $body['serial'])
            ->getQuery()
            ->getArrayResult();

        if (!empty($isPhysicalSerialBeingUsedElsewhere)) {
            return Http::status(409, [
                'reason' => 'PHYSICAL_SERIAL_ALREADY_IN_USE',
                'location' => $isPhysicalSerialBeingUsedElsewhere[0]['virtualSerial'],
            ]);
        }

        $this->em->createQueryBuilder()
            ->update(MikrotikSymlinkSerial::class, 'u')
            ->set('u.physicalSerial', ':serial')
            ->where('u.virtualSerial = :locationSerial')
            ->setParameter('serial', $body['serial'])
            ->setParameter('locationSerial', $serial)
            ->getQuery()
            ->execute();

        return Http::status(200);
    }
}
