<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 13/08/2017
 * Time: 09:10
 */

namespace App\Controllers\Marketing;

use App\Models\Locations\LocationSettings;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEventOptions;
use App\Models\MarketingEvents;
use App\Models\MarketingLocations;
use App\Models\MarketingMessages;
use App\Models\UserData;
use App\Models\UserProfile;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _MarketingRunner
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function testGetAudienceRoute(Request $request, Response $response)
    {
        $campaign = $this->em->createQueryBuilder()
                        ->select('u')
                        ->from(MarketingCampaigns::class, 'u')
                        ->where('u.id = :id')
                        ->setParameter('id', $request->getAttribute('campaignId'))
                        ->getQuery()
                        ->getArrayResult()[0];


        $send = $this->getAudience($campaign);

        return $response->withJson($send, 200);
    }

    public function activeCampaigns()
    {
        $res       = [];
        $campaigns = $this->em->createQueryBuilder()
            ->select('
            c.id,
            c.active,
            c.hasLimit,
            c.eventId,
            c.edited,
            c.messageId,
            c.templateId,
            c.admin,
            c.deleted,
            c.limit'
            )
            ->from(MarketingCampaigns::class, 'c')
            ->where('c.deleted = false')
            ->andWhere('c.active = true')
            ->getQuery()
            ->getArrayResult();

        $ids      = [];
        $eventIds = [];
        foreach ($campaigns as $campaign) {
            $campaign['locations'] = [];
            $campaign['rules']     = [];
            if ($campaign['hasLimit'] === false) {
                $res[]      = $campaign;
                $ids[]      = $campaign['id'];
                $eventIds[] = $campaign['eventId'];
            }
            if ($campaign['hasLimit'] === true && $campaign['limit'] >= 1) {
                $res[]      = $campaign;
                $ids[]      = $campaign['id'];
                $eventIds[] = $campaign['eventId'];
            }
        }

        $locations = $this->em->createQueryBuilder()
            ->select('
            u.serial,
            u.campaignId')
            ->from(MarketingLocations::class, 'u')
            ->where('u.campaignId IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();


        $operands = $this->em->createQueryBuilder()
            ->select('
            u.id,
            u.eventId,
            u.event,
            u.operand,
            u.condition,
            u.position,
            u.value')
            ->from(MarketingEventOptions::class, 'u')
            ->where('u.eventId IN (:id)')
            ->setParameter('id', $eventIds)
            ->orderBy('u.position', 'ASC')
            ->getQuery()
            ->getArrayResult();


        foreach ($res as $key => $campaign) {
            foreach ($operands as $operand) {
                if ($campaign['eventId'] === $operand['eventId']) {
                    $res[$key]['rules'][] = $operand;
                }
            }
            foreach ($locations as $location) {
                if ($location['campaignId'] === $campaign['id']) {
                    $res[$key]['locations'][] = $location['serial'];
                }
            }
        }

        return $res;
    }

    public function getAudience(array $campaign)
    {

        if (is_null($campaign['edited'])) {
            return [];
        }

        $editedTimestamp = $campaign['edited']->getTimestamp();

        $campaignEdited = new \DateTime();
        $campaignEdited->setTimestamp($editedTimestamp);

        $threeMonths = new \DateTime();
        $threeMonths->modify('- 3 months');
        if ($campaignEdited > $threeMonths->getTimestamp()) {
            $campaignEdited = $threeMonths;
        }

        $profiles = $this->em->createQueryBuilder()
            ->select(
                'u.id,
                        u.phone,
                        u.phoneValid,
                        u.email,
                        u.verified,
                        u.first,
                        u.last,
                        u.country,
                        u.gender,
                        u.birthDay,
                        u.birthMonth,
                        ud.serial,
                        UNIX_TIMESTAMP(MAX(ud.timestamp)) AS joined,
                        UNIX_TIMESTAMP(MAX(ud.lastupdate)) AS lastupdate,
                        MAX(ud.lastupdate) AS humanLast,
                        TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)as uptime,
                        COUNT(ud.profileId) AS connections,
                        ns.alias AS alias,
                        me.timestamp'
            )
            ->from(UserData::class, 'ud')
            ->innerJoin(UserProfile::class, 'u', 'WITH', 'ud.profileId = u.id')
            ->leftJoin(MarketingEvents::class, 'me', 'WITH', 'me.campaignId = :campaignId AND me.profileId = u.id')
            ->leftJoin(LocationSettings::class, 'ns', 'WITH', 'ns.serial = ud.serial')
            ->where('ud.serial IN (:serials)')
            ->andWhere('me.timestamp IS NULL')
            ->andWhere('u.email IS NOT NULL')
            ->andWhere('ud.timestamp > :time')
            ->setParameter('campaignId', $campaign['id'])
            ->setParameter('time', $campaignEdited)
            ->setParameter('serials', $campaign['locations'])
            ->groupBy('u.id')
            ->orderBy('ud.lastupdate', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return $profiles;
    }
}
