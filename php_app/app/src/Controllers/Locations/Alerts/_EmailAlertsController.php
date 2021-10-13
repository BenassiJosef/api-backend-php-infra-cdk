<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/12/2016
 * Time: 19:20
 */

namespace App\Controllers\Locations\Alerts;

use App\Controllers\Integrations\Mail\_MailController;
use App\Models\EmailAlerts;
use App\Models\Locations\LocationSettings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _EmailAlertsController
{

    protected $em;
    protected $mail;

    public function __construct(EntityManager $em, _MailController $mail)
    {
        $this->em   = $em;
        $this->mail = $mail;
    }

    public function sendRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');

        $send = $this->send($request->getParsedBody(), $serial);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function send(string $serial, array $body)
    {
        $getSiteInfo = $this->findBySerial($serial);

        $send = $this->mail->send(
            [
                'to' => $body['to']
            ],
            [
                'alias' => $getSiteInfo['alias'],
                'text'  => $getSiteInfo['alias'] . ' : ' . $body['alertType'],
                'title' => $getSiteInfo['alertType'] . ' Notification'
            ],
            'AlertsTemplate',
            $getSiteInfo['alertType'] . ' Notification'
        );

        if ($send['status'] === 200) {
            return Http::status(200, 'MAIL_SENT');
        }

        return Http::status(400, 'MAIL_NOT_SENT');
    }

    public function findBySerial(string $serial)
    {

        $results = $this->em->createQueryBuilder()
            ->select('e, l.alias')
            ->from(EmailAlerts::class, 'e')
            ->leftJoin(LocationSettings::class, 'l', 'WITH', 'l.serial = e.serial')
            ->where('e.serial = :serial')
            ->setParameter('serial', $serial)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        $send = [];

        if (!empty($results)) {
            $send = $results[0];
        }

        return $send;

    }
}
