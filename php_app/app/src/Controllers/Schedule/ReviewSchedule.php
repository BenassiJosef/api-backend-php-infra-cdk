<?php

/**
 * Created by jamieaitken on 06/08/2018 at 09:16
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Schedule;

use App\Models\Billing\Organisation\Subscriptions;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\Integrations\Facebook\FacebookPages;
use App\Models\Locations\Reviews\LocationReviews;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Position\LocationPosition;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;
use App\Controllers\Billing\Subscription;
use App\Models\Reviews\ReviewSettings;
use App\Package\Organisations\OrganizationService;

class ReviewSchedule
{

    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var Subscription $subscription
     */
    protected $subscription;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->subscription = new Subscription(new OrganizationService($this->em), $this->em);
    }

    public function getReviewsRoute(Request $request, Response $response)
    {
        $send = $this->getReviews();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getReviews()
    {
        /**

         * @var ReviewSettings[] $reviews
         */
        $reviews = $this->em->getRepository(ReviewSettings::class)->findBy([
            'isActive' => true
        ]);
        $client = new QueueSender();
        $handledReviews = [];
        foreach ($reviews as $review) {
            $subscription = $this->subscription->getOrganisationSubscription($review->getOrganizationId());

            if (!$subscription || !$subscription->hasAddon(Subscriptions::ADDON_REVIEWS) || !$subscription->isSubscriptionValid()) {
                continue;
            }
            if (!is_null($review->getGooglePlaceId())) {
                $client->sendMessage(
                    [
                        'resourceId' => $review->getGooglePlaceId(),
                        'serial'     => $review->getSerial(),
                        'setting_id'   => $review->getId()->toString()
                    ],
                    QueueUrls::GOOGLE_REVIEWS
                );
            }
            if (!is_null($review->getTripadvisorUrl())) {
                $client->sendMessage(
                    [
                        'resourceId' => $review->getTripadvisorUrl(),
                        'serial'     => $review->getSerial(),
                        'setting_id'   => $review->getId()->toString()
                    ],
                    QueueUrls::TRIPADVISOR_REVIEWS
                );
            }

            if (!is_null($review->getFacebookPageId())) {
                $client->sendMessage(
                    [
                        'resourceId' => $review->getFacebookPageId(),
                        'serial'     => $review->getSerial(),
                        'setting_id'   => $review->getId()->toString()
                    ],
                    QueueUrls::FACEBOOK_REVIEWS
                );
            }
        }





        return Http::status(200, $handledReviews);
    }
}
