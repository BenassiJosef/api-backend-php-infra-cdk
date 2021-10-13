<?php

namespace App\Controllers\Integrations\Hooks;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Integrations\Hooks;
use App\Models\UserProfile;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _HooksController
{

    protected $em;
    protected $infrastructureCache;

    public function __construct(EntityManager $em)
    {
        $this->em                  = $em;
        $this->infrastructureCache = new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
    }

    public function sendRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->send($body['target_url'], $body['payload']);

        $this->em->clear();

        return $response->withJson($send);
    }

    public function getAllRoute(Request $request, Response $response)
    {

        $uid = $request->getAttribute('accessUser')['uid'];

        $this->em->clear();

        return $response->withJson($this->getAll($uid));
    }

    public function getAll($uid)
    {
        $hooks = $this->em->getRepository(Hooks\Hook::class)->findBy(
            [
                'createdBy' => $uid,
                'deleted'   => false
            ]
        );

        if (!is_array($hooks)) {
            return Http::status(404);
        }

        $res = [];
        foreach ($hooks as $hook) {
            $res[] = $hook->getArrayCopy();
        }

        return Http::status(200, $res);
    }

    public function subscribeRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $uid  = $request->getAttribute('user')['uid'];

        $subscribe = $this->subscribe($uid, $body);

        $this->em->clear();

        return $response->withJson($subscribe, $subscribe['status']);
    }

    public function subscribe(string $admin, array $body)
    {
        if (!isset($body['target_url'])) {
            return Http::status(400, 'INVALID_REQUEST_TARGET_MISSING');
        }
        if (!isset($body['event'])) {
            $this->unsubscribe($body['target_url']);

            return Http::status(200, 'UNSUBSCRIBED');
        }


        if (isset($body['payload'])) {
            foreach ($body['payload'] as $item) {
                foreach ($item as $value) {
                    $hook        = new Hooks\Hook($body['target_url'], $admin, $body['event']);
                    $hook->param = $value;
                    $this->em->persist($hook);
                    $this->infrastructureCache->delete('hooks:' . $hook->param . ':' . $hook->event);
                }
            }
        } else {
            $hook = new Hooks\Hook($body['target_url'], $admin, $body['event']);
            $this->em->persist($hook);
        }


        $this->em->flush();

        return Http::status(200, 'CUSTOMER_SUBSCRIBED');
    }

    public function send($target_url, $payload = [])
    {
        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/json');
        $curl->post($target_url, $payload);

        $mp = new _Mixpanel();
        $mp->track('zapier_send', $payload);

        return $curl->response;
    }

    public function unsubscribe($endpoint)
    {

        $hook = $this->em->getRepository(Hooks\Hook::class)->findOneBy(['target_url' => $endpoint]);
        if (is_object($hook)) {
            $hook->deleted = true;
            $cacheKey      = 'hooks:' . $hook->serial . ':' . $hook->event;
            $this->infrastructureCache->delete($cacheKey);
        }

        $this->em->persist($hook);
        $this->em->flush();
    }

    public function serialToHook($serial, $event, $payload)
    {
        if (function_exists("newrelic_add_custom_parameter")) {
            newrelic_add_custom_parameter("event_serial", $serial);
            newrelic_add_custom_parameter("event_type", $event);
        }

        $publisher = new QueueSender();

        $user = $payload;

        if (!empty($user['custom'][$serial])) {
            foreach ($user['custom'][$serial] as $id => $value) {
                $user[$id] = $value;
            }
        }

        $publisher->sendMessage(
            [
                'user'   => $user,
                'event'  => $event,
                'serial' => $serial
            ],
            QueueUrls::CAMPAIGN_MONITOR
        );


        $publisher->sendMessage(
            [
                'user'   => $user,
                'event'  => $event,
                'serial' => $serial
            ],
            QueueUrls::DOT_MAILER
        );

        $publisher->sendMessage(
            [
                'user'   => $user,
                'event'  => $event,
                'serial' => $serial
            ],
            QueueUrls::MAIL_CHIMP
        );

        $publisher->sendMessage(
            [
                'user'   => $user,
                'event'  => $event,
                'serial' => $serial
            ],
            QueueUrls::TEXT_LOCAL
        );

        $publisher->sendMessage(
            [
                'user'   => $user,
                'event'  => $event,
                'serial' => $serial
            ],
            QueueUrls::AIRSHIP
        );

        $publisher->sendMessage(
            [
                'serial'  => $serial,
                'event'   => $event,
                'payload' => $payload
            ],
            QueueUrls::ZAPIER
        );
    }
}
