<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 22/03/2017
 * Time: 18:22
 */

namespace App\Controllers\Locations\Devices;

use App\Controllers\Integrations\Mikrotik\_MikrotikDeviceController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Models\NetworkWhitelist;
use App\Utils\Http;
use App\Utils\Validation;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _WhitelistController extends _LocationSettingsController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function getRoute(Request $request, Response $response, $args)
    {
        $serial = $request->getAttribute('serial');
        $send   = $this->getAll($serial);

        $this->em->clear();

        return $response->withStatus($send['status'])->withJson($send);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $id       = $request->getAttribute('id');
        $send     = $this->delete($id);
        $loggedIn = $request->getAttribute('user');

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('whitelist_delete', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $loggedIn = $request->getAttribute('user');
        $serial   = $request->getAttribute('serial');
        $id       = $request->getAttribute('id');
        $body     = $request->getParsedBody();

        $validation = Validation::bodyCheck($request, ['alias', 'mac']);
        if ($validation === true) {
            $send = $this->update($body, $id, $serial);
        } else {
            $send = Http::status(400, $validation);
        }

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('whitelist_update', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function postRoute(Request $request, Response $response, $args)
    {
        $serial   = $request->getAttribute('serial');
        $loggedIn = $request->getAttribute('user');
        $body     = $request->getParsedBody();

        $validation = Validation::bodyCheck($request, ['alias', 'mac']);

        if ($validation === true) {
            $send = $this->create($body, $serial);
        } else {
            $send = Http::status(400, $validation);
        }

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('whitelist_create', $send);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }


    public function delete(int $id)
    {

        $whitelist = $this->em->getRepository(NetworkWhitelist::class)->findOneBy([
            'id'      => $id,
            'deleted' => false
        ]);

        if (is_null($whitelist)) {
            return Http::status(404);
        }

        $mikrotik = new _MikrotikDeviceController($this->em);
        $mikrotik->deleteWhitelist($id, $whitelist->serial);

        $whitelist->deleted = true;
        $this->em->persist($whitelist);
        $this->em->flush();

        return Http::status(200, $id);
    }

    public function getAll(string $serial)
    {
        $whitelist = $this->em->getRepository(NetworkWhitelist::class)->findBy([
            'serial'  => $serial,
            'deleted' => false
        ]);

        $res = [];
        foreach ($whitelist as $item) {
            $res[] = $item->getArrayCopy();
        }

        return Http::status(200, $res);
    }

    public function create(array $body, string $serial)
    {
        $whitelist = new NetworkWhitelist($body['alias'], $body['mac'], $serial);
        $this->em->persist($whitelist);
        $this->em->flush();

        $mikrotik = new _MikrotikDeviceController($this->em);
        $mikrotik->addWhitelist($whitelist->mac, $whitelist->id, $whitelist->serial);

        return Http::status(200, $whitelist->getArrayCopy());
    }

    function update(array $body, string $id, string $serial)
    {
        $whitelist = $this->em->getRepository(NetworkWhitelist::class)->findOneBy([
            'serial'  => $serial,
            'id'      => $id,
            'deleted' => false
        ]);

        if (is_null($whitelist)) {
            return Http::status(404);
        }

        if ($body['mac'] !== $whitelist->mac) {
            $mikrotik = new _MikrotikDeviceController($this->em);
            $mikrotik->deleteWhitelist($whitelist->id, $serial);
            $mikrotik->addWhitelist($body['mac'], $whitelist->id, $serial);
        }

        $whitelist->alias = $body['alias'];
        $whitelist->mac   = $body['mac'];
        $this->em->persist($whitelist);
        $this->em->flush();

        return Http::status(200, $whitelist->getArrayCopy());
    }
}
