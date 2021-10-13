<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 23/03/2017
 * Time: 12:38
 */

namespace App\Controllers\Locations\Super;

use App\Controllers\Integrations\Mikrotik\_MikrotikConfigController;
use App\Controllers\Integrations\Mikrotik\_MikrotikDeviceController;
use App\Controllers\Integrations\Mikrotik\_MikrotikExportController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _SuperController extends _LocationSettingsController
{

    /**
     * _SuperController constructor.
     * @param EntityManager $em
     */

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * body should contain array of serials => ['serial_01','serial_02']
     * and a command eg '/system reboot'
     */

    public function bulkUpdate(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!isset($body['serials'])) {
            $send = Http::status(400, 'MISSING_SERIALS');

            return $response->withJson($send, $send['status']);
        }

        if (!isset($body['command'])) {
            $send = Http::status(400, 'MISSING_COMMAND');

            return $response->withJson($send, $send['status']);
        }

        $mikrotikController = new _MikrotikConfigController($this->em);
        foreach ($body['serials'] as $serial) {
            $mikrotikController->buildConfigSilent($body['command'], $serial);
        }

        $send = Http::status(200, 'COMMAND_SENT_OFF');

        return $response->withJson($send, $send['status']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     */

    public function postRoute(Request $request, Response $response)
    {

        $body     = $request->getParsedBody();
        $loggedIn = $request->getAttribute('user');
        $serial   = $request->getAttribute('serial');

        if (isset($body['type'])) {
            switch ($body['type']) {
                case 'COMMAND':
                    $mikrotikController = new _MikrotikExportController($this->em);
                    $mikrotikController->genericCommand($body['command'], $serial);
                    break;
                case 'EXPORT':
                    $mikrotikController = new _MikrotikExportController($this->em);
                    $mikrotikController->export($body['to'], $serial);
                    break;
                case 'DNS':
                    $mikrotikController = new _MikrotikDeviceController($this->em);
                    $mikrotikController->setDNS($body['dns'], $serial);
                    break;
            }
        }

        $send = Http::status(200, 'COMMAND_SENT_OFF');

        $mp = new _Mixpanel();
        $mp->identify($loggedIn['uid'])->track('super_saved', $body);

        return $response->withJson($send, $send['status']);
    }
}
