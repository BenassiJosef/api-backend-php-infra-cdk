<?php
/**
 * Created by jamieaitken on 28/05/2018 at 16:17
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Settings\Policy;

use App\Models\Locations\LocationPolicyGroup;
use App\Models\Locations\LocationPolicyGroupSerials;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class LocationPolicyController
{
    protected $em;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function createPolicyRoute(Request $request, Response $response)
    {
        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->createPolicy($organization, $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function listPolicyBelongingToAdminRoute(Request $request, Response $response)
    {

        $send = $this->listPolicyBelongingToAdmin($request->getAttribute('orgId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSitesBelongingToGroupRoute(Request $request, Response $response)
    {
        $send = $this->getSitesBelongingToGroup(
            $request->getAttribute('groupId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function addSiteToPolicyRoute(Request $request, Response $response)
    {
        $send = $this->addSiteToPolicy($request->getAttribute('groupId'), $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteSiteFromPolicyRoute(Request $request, Response $response)
    {

        $send = $this->deleteSiteFromPolicy($request->getAttribute('groupId'), $request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteGroupRoute(Request $request, Response $response)
    {
        $send = $this->deleteGroup($request->getAttribute('groupId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePolicyNameRoute(Request $request, Response $response)
    {
        $send = $this->updatePolicyName($request->getAttribute('groupId'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createPolicy(Organization $organization, array $body)
    {
        $validate = Validation::pastRouteBodyCheck($body, ['name']);

        if (is_array($validate)) {
            return Http::status(400, 'MISSING_' . implode(',', $validate));
        }

        $newPolicy = new LocationPolicyGroup($organization, $body['name']);
        $this->em->persist($newPolicy);

        $this->em->flush();

        return Http::status(200, $newPolicy->getArrayCopy());
    }

    public function listPolicyBelongingToAdmin(string $orgId)
    {
        $policies = $this->em->createQueryBuilder()
            ->select('u.id, u.name')
            ->from(LocationPolicyGroup::class, 'u')
            ->where('u.organizationId = :orgId')
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($policies)) {
            return Http::status(204);
        }

        return Http::status(200, $policies);
    }

    public function getSitesBelongingToGroup(string $groupId)
    {
        $sites = $this->em->createQueryBuilder()
            ->select('ls.alias, ls.serial, u.id, u.name')
            ->from(LocationPolicyGroup::class, 'u')
            ->leftJoin(LocationPolicyGroupSerials::class, 'o', 'WITH', 'o.groupId = u.id')
            ->leftJoin(LocationSettings::class, 'ls', 'WITH', 'o.serial = ls.serial')
            ->where('u.id = :group')
            ->setParameter('group', $groupId)
            ->getQuery()
            ->getArrayResult();

        foreach ($sites as $key => $site) {
            if (is_null($site['serial'])) {
                array_splice($sites, $key, 1);
            }
        }

        if (empty($sites)) {
            return Http::status(204);
        }

        $response = [
            'id'    => $sites[0]['id'],
            'name'  => $sites[0]['name'],
            'sites' => []
        ];

        foreach ($sites as $site) {
            $response['sites'][] = [
                'serial' => $site['serial'],
                'alias'  => $site['alias']
            ];

        }


        return Http::status(200, $response);
    }

    public function updatePolicyName(string $groupId, array $body)
    {
        if (!isset($body['name'])) {
            return Http::status(400, 'NO_NAME_SET');
        }

        $update = $this->em->getRepository(LocationPolicyGroup::class)->findOneBy([
            'id' => $groupId
        ]);

        if (is_null($update)) {
            return Http::status(400, 'INVALID_GROUP_ID');
        }

        $update->name = $body['name'];

        $this->em->flush();

        return Http::status(200, $update->getArrayCopy());
    }

    public function addSiteToPolicy(string $groupId, string $serial)
    {
        $addSite = new LocationPolicyGroupSerials($groupId, $serial);
        $this->em->persist($addSite);

        $this->em->flush();

        return Http::status(200, $addSite->getArrayCopy());
    }

    public function deleteSiteFromPolicy(string $groupId, string $serial)
    {
        $this->em->createQueryBuilder()
            ->delete(LocationPolicyGroupSerials::class, 'u')
            ->where('u.groupId = :group')
            ->andWhere('u.serial = :serial')
            ->setParameter('group', $groupId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        return Http::status(200, [
            'id'     => $groupId,
            'serial' => $serial
        ]);
    }

    public function deleteGroup(string $groupId)
    {

        $validation = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(LocationPolicyGroup::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $groupId)
            ->getQuery()
            ->getArrayResult();

        if (empty($validation)) {
            return Http::status(400, 'INVALID_POLICY_ID');
        }

        $this->em->createQueryBuilder()
            ->delete(LocationPolicyGroupSerials::class, 'u')
            ->where('u.groupId = :group')
            ->setParameter('group', $groupId)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(LocationPolicyGroup::class, 'u')
            ->where('u.id = :group')
            ->setParameter('group', $groupId)
            ->getQuery()
            ->execute();

        return Http::status(200, ['id' => $groupId]);
    }
}