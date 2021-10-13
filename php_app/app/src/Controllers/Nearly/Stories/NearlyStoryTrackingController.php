<?php
/**
 * Created by jamieaitken on 12/03/2019 at 16:25
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\Stories;

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Models\Nearly\Stories\NearlyStoryPage;
use App\Models\Nearly\Stories\NearlyStoryPageActivity;
use App\Models\Nearly\Stories\NearlyStoryPageActivityAggregate;
use App\Models\NetworkAccess;
use App\Models\UserProfile;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class NearlyStoryTrackingController
{
    protected $em;
    protected $mail;

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }

    public function trackRoute(Request $request, Response $response)
    {

        $send = $this->track($request->getAttribute('action'), $request->getAttribute('trackingId'));

        return $response->withJson($send, $send['status']);
    }

    public function track(string $action, string $trackingId)
    {
        $actions = ['impression', 'clicked', 'conversion'];

        if (!in_array($action, $actions)) {
            return Http::status(409, 'INVALID_ACTION');
        }

        $getActivity = $this->em->getRepository(NearlyStoryPageActivity::class)->findOneBy([
            'id' => $trackingId
        ]);

        if (is_null($getActivity)) {
            return Http::status(409, 'INVALID_TRACKING_ID');
        }

        $formattedDate = new \DateTime();
        $formattedDate = new \DateTime($formattedDate->format('Y-m-d H:00:00'));

        $activityAggregateExists = $this->em->getRepository(NearlyStoryPageActivityAggregate::class)->findOneBy([
            'pageId'             => $getActivity->pageId,
            'serial'             => $getActivity->serial,
            'formattedTimestamp' => $formattedDate
        ]);

        if (is_null($activityAggregateExists)) {
            $activityAggregateExists = new NearlyStoryPageActivityAggregate($getActivity->pageId, $getActivity->serial);
            $this->em->persist($activityAggregateExists);
        }

        if ($action === 'impression') {
            $getActivity->impression              = true;
            $getActivity->impressionCreatedAt     = new \DateTime();
            $activityAggregateExists->impressions += 1;
        } elseif ($action === 'clicked') {
            $getActivity->clicked            = true;
            $getActivity->clickCreatedAt     = new \DateTime();
            $activityAggregateExists->clicks += 1;

            $dataOptOutCheck = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(LocationOptOut::class, 'u')
                ->where('u.profileId = :profileId')
                ->andWhere('u.serial = :serial')
                ->andWhere('u.deleted = :false')
                ->setParameter('profileId', $getActivity->profileId)
                ->setParameter('serial', $getActivity->serial)
                ->setParameter('false', true)
                ->getQuery()
                ->getArrayResult();

            if (empty($dataOptOutCheck)) {
                return false;
            }

            $marketingOptOutCheck = $this->em->createQueryBuilder()
                ->select('u.id')
                ->from(MarketingOptOut::class, 'u')
                ->where('u.uid = :profileId')
                ->andWhere('u.serial = :serial')
                ->andWhere('u.optOut = :true')
                ->setParameter('profileId', $getActivity->profileId)
                ->setParameter('serial', $getActivity->serial)
                ->setParameter('true', false)
                ->getQuery()
                ->getArrayResult();

            if (empty($marketingOptOutCheck)) {
                return false;
            }
        } elseif ($action === 'conversion') {
            $getActivity->conversion              = true;
            $getActivity->conversionCreatedAt     = new \DateTime();
            $activityAggregateExists->conversions += 1;
        }

        $this->em->flush();

        return Http::status(200);
    }
}