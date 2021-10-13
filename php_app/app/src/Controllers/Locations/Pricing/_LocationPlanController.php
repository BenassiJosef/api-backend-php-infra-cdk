<?php

namespace App\Controllers\Locations\Pricing;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/01/2017
 * Time: 15:07
 */

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Models\LocationPlan;
use App\Models\LocationPlanSerial;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationPlanController
{
    protected $em;
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    //Begin Routes

    public function createNewPlanRoute(Request $request, Response $response)
    {
        $body     = $request->getParsedBody();
        $user     = $request->getAttribute('accessUser');
        $loggedIn = $request->getAttribute('user');

        $serial = $request->getAttribute('serial');

        $send = $this->createNewPlan($body, $serial, $user['uid']);
        $mp   = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('payment_plan_create', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function receivePlanRoute(Request $request, Response $response)
    {
        $id   = $request->getAttribute('planId');
        $user = $request->getAttribute('accessUser');

        $send = $this->receivePlan($id, $user['uid']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function receiveAllPlansRoute(Request $request, Response $response)
    {
        $user = $request->getAttribute('accessUser');
        $send = $this->receiveAllPlans($user['uid']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function receiveAllPlansForSerialRoute(Request $request, Response $response)
    {
        $send = $this->receiveAllPlansFromSerial($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updatePlanRoute(Request $request, Response $response)
    {

        $body     = $request->getParsedBody();
        $planId   = $request->getAttribute('planId');
        $loggedIn = $request->getAttribute('user');
        $send     = $this->updatePlan($body, $planId);

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('payment_plan_update', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deletePlanRoute(Request $request, Response $response)
    {
        $planId   = $request->getAttribute('planId');
        $send     = $this->deletePlan($planId, $request->getAttribute('serial'));
        $loggedIn = $request->getAttribute('user');

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('payment_plan_delete', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    //End Routes

    public function createNewPlan(array $body, string $serial, string $adminId = '')
    {
        if (!isset($body['name'])) {
            return Http::status(400, 'NAME_REQUIRED');
        }

        if (!isset($body['deviceAllowance'])) {
            return Http::status(400, 'DEVICE_ALLOWANCE_REQUIRED');
        }

        if (!isset($body['duration'])) {
            return Http::status(400, 'DURATION_REQUIRED');
        }

        if (!isset($body['cost'])) {
            return Http::status(400, 'COST_REQUIRED');
        }

        if ($body['cost'] < 30) {
            return Http::status(402, 'DOES_NOT_MEET_MINIMUM_CHARGE_OF_0.30_GBP');
        }

        $newPlan = new LocationPlan(
            $adminId,
            $body['name'],
            $body['deviceAllowance'],
            $body['duration'],
            $body['cost']
        );

        $this->em->persist($newPlan);
        $this->em->flush();

        $newPlanForSerial = new LocationPlanSerial(
            $newPlan->id,
            $serial
        );

        $this->em->persist($newPlanForSerial);
        $this->em->flush();

        $this->nearlyCache->delete($serial . ':plans');


        return Http::status(200, $newPlan->getArrayCopy());
    }

    public function receivePlan(string $planId = '', string $adminId = '')
    {
        $get = $this->em->createQueryBuilder()
            ->select('p')
            ->from(LocationPlan::class, 'p')
            ->where('p.id = :pl')
            ->andWhere('p.adminId = :a') // TODO OrgId replace
            ->andWhere('p.isDeleted = 0')
            ->setParameter('pl', $planId)
            ->setParameter('a', $adminId)
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(400, 'COULD_NOT_LOCATE_PLAN');
        }

        return Http::status(200, $get[0]);
    }

    public function receiveAllPlans(string $adminId = '')
    {
        $get = $this->em->createQueryBuilder()
            ->select('p')
            ->from(LocationPlan::class, 'p')
            ->where('p.adminId = :admin') // TODO OrgId replace
            ->andWhere('p.isDeleted = 0')
            ->setParameter('admin', $adminId)
            ->getQuery()
            ->getArrayResult();

        if (!empty($get)) {
            return Http::status(200, $get);
        }

        return Http::status(400, 'COULD_NOT_LOCATE_ANY_PLANS');
    }

    public function receiveAllPlansFromSerial(string $serial)
    {
        $exists = $this->nearlyCache->fetch($serial . ':plans');
        if (!is_bool($exists)) {
            return Http::status(200, $exists);
        }

        $get = $this->em->createQueryBuilder()
            ->select('lp')
            ->from(LocationPlan::class, 'lp')
            ->join(LocationPlanSerial::class, 'lps', 'WITH', 'lp.id = lps.planId')
            ->where('lps.serial = :serial')
            ->andWhere('lp.isDeleted = 0')
            ->setParameter('serial', $serial)
            ->orderBy('lp.cost', 'ASC')
            ->getQuery()
            ->getArrayResult();

        if (empty($get)) {
            return Http::status(204, 'LOCATION_HAS_NO_PLANS');
        }

        $this->nearlyCache->save($serial . ':plans', $get);

        return Http::status(200, $get);
    }

    public function updatePlan(array $body = [], string $planId = '')
    {
        $canChange = ['name', 'deviceAllowance', 'duration', 'cost'];

        $data = $this->em->getRepository(LocationPlan::class)->findOneBy(['id' => $planId]);

        if (is_null($data)) {
            return Http::status(400, 'FAILED_TO_LOCATE_PLAN');
        }

        foreach ($body as $key => $item) {
            if (in_array($key, $canChange)) {
                $data->$key = $item;
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        $this->nearlyCache->delete($body['serial'] . ':plans');


        return Http::status(200, $data->getArrayCopy());
    }

    public function deletePlan(string $planId, string $serial)
    {
        $this->em->createQueryBuilder()
            ->update(LocationPlan::class, 'p')
            ->set('p.isDeleted', 1)
            ->where('p.id = :e')
            ->setParameter('e', $planId)
            ->getQuery()
            ->execute();


        $getLocationsWherePlanIsBeingUsed = $this->em->createQueryBuilder()
            ->select('u.serial')
            ->from(LocationPlanSerial::class, 'u')
            ->where('u.planId = :plan')
            ->setParameter('plan', $planId)
            ->getQuery()
            ->getArrayResult();

        $cacheKeys = [];

        foreach ($getLocationsWherePlanIsBeingUsed as $location) {
            $cacheKeys[] = $location['serial'] . ':plans';
        }

        $this->nearlyCache->deleteMultiple($cacheKeys);

        $this->em->createQueryBuilder()
            ->delete(LocationPlanSerial::class, 'u')
            ->where('u.planId = :plan')
            ->andWhere('u.serial = :serial')
            ->setParameter('plan', $planId)
            ->setParameter('serial', $serial)
            ->getQuery()
            ->execute();

        return Http::status(200, ['id' => $planId]);
    }

    public function retriveAdminFromPlan(string $planId = '')
    {
        $adminPlan = $this->em->createQueryBuilder()
            ->select('p.adminId')
            ->from(LocationPlan::class, 'p')
            ->where('p.id = :planId')
            ->setParameter('planId', $planId)
            ->getQuery()
            ->getArrayResult();

        if (!empty($adminPlan)) {
            return $adminPlan[0]['adminId'];
        }

        return false;
    }

    public function getPlanFromId(string $planId)
    {
        $planQuery = $this->em->createQueryBuilder()
            ->select('
            u.id,
            u.name,
            u.deviceAllowance,
            u.duration,
            u.cost')
            ->from(LocationPlan::class, 'u')
            ->where('u.id = :i')
            ->setParameter('i', $planId)
            ->getQuery()
            ->getArrayResult();

        return $planQuery[0];
    }
}
