<?php
/**
 * Created by jamieaitken on 04/07/2018 at 16:04
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Type;

use App\Models\Locations\Type\LocationTypes;
use App\Models\Locations\Type\LocationTypesSerial;
use App\Models\NetworkAccess;
use App\Package\Organisations\OrganisationIdProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationTypeSerialReportController
{
    protected $em;
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->orgIdProvider = new OrganisationIdProvider($this->em);

    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('accessUser'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(array $user)
    {
        $orgOrAdminId = $this->orgIdProvider->getIds($user['uid']);

        $get = $this->em->createQueryBuilder()
            ->select('u')
            ->from(LocationTypesSerial::class, 'u')
            ->leftJoin(LocationTypes::class, 'lt', 'WITH', 'u.locationTypeId = lt.id')
            ->leftJoin(NetworkAccess::class, 'a', 'WITH', 'u.serial = a.serial')
            ->where('u.admin = :uid OR u.organizationId = :orgId')
            ->setParameter('uid', $orgOrAdminId->getAdminId())
            ->setParameter('orgId', $orgOrAdminId->getOrgId())
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204);
        }

        return Http::status(200, $get);
    }
}