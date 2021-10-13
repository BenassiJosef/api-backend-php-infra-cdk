<?php


namespace App\Controllers\Stats;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Marketing\_MarketingCallBackController;
use App\Models\Marketing\MarketingDeliverable;
use App\Models\NetworkAccess;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Utils\Http;
use App\Utils\Validation;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Plivo\RestAPI;
use Plivo\RestClient;
use Slim\Http\Response;
use Slim\Http\Request;
use Twilio\Rest\Client;

class StatsController
{

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    public function getRoute(Request $request, Response $response)
    {
        $send = $this->getData();

        return $response->withJson($send, $send['status']);
    }

    public function getData()
    {


        $connections = $this->em->createQueryBuilder()
            ->select('COUNT(u.id) as connections')
            ->from(UserData::class, 'u')
            ->getQuery()
            ->getArrayResult();

        $registrations = $this->em->createQueryBuilder()
            ->select('COUNT(p.id) as registrations')
            ->from(UserProfile::class, 'p')
            ->getQuery()
            ->getArrayResult();

        $sent = $this->em->createQueryBuilder()
            ->select('COUNT(e.messageId) as emails_sent')
            ->from(MarketingDeliverable::class, 'e')
            ->getQuery()
            ->getArrayResult();

        $sites = $this->em->createQueryBuilder()
            ->select('COUNT(e.serial) as sites')
            ->from(NetworkAccess::class, 'e')
            ->where('e.admin IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $mrr  = 0;
        $curl = new Curl();
        $curl->get('https://mrr.stampede.ai');

        if (!$curl->error) {
            $mrr = $curl->response->number;
        }

        $returnArray = [
            'connections'   => $connections[0]['connections'],
            'registrations' => $registrations[0]['registrations'],
            'emails_sent'   => $sent[0]['emails_sent'],
            'sites'         => $sites[0]['sites'],
            'mrr'           => $mrr,
            'date'          => date("Y-m-d H:i:s", time())
        ];

        return Http::status(200, $returnArray);
    }

}