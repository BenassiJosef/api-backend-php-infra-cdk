<?php
/**
 * Created by jamieaitken on 07/08/2018 at 15:35
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reviews;

use App\Models\Locations\Reviews\LocationReviews;
use App\Models\Locations\Reviews\LocationReviewsTimeline;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class LocationReviewTimelineController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'), $request->getQueryParams());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $serial, array $queryParams)
    {
        $reviewTypeSql = '';
        $all           = [];

        if (isset($queryParams['facebook'])) {
            if ($queryParams['facebook'] === 'true') {
                $all[] = 'facebook';

            }
        }

        if (isset($queryParams['tripAdvisor'])) {
            if ($queryParams['tripAdvisor'] === 'true') {
                $all[] = 'tripAdvisor';

            }
        }

        if (isset($queryParams['blackbx'])) {
            if ($queryParams['blackbx'] === 'true') {
                $all[] = 'blackbx';
            }
        }

        if (isset($queryParams['google'])) {
            if ($queryParams['google'] === 'true') {
                $all[] = 'google';
            }
        }

        $getReviews = $this->em->createQueryBuilder()
            ->select('s.reviewType, u.id, u.oneStarRatings, u.twoStarRatings, u.threeStarRatings, u.fourStarRatings,
             u.fiveStarRatings, u.createdAt, u.overallRating')
            ->from(LocationReviews::class, 's')
            ->leftJoin(LocationReviewsTimeline::class, 'u', 'WITH', 's.id = u.reviewId')
            ->where('s.serial = :ser');
        if (sizeof($all) > 0 && sizeof($all) < 4) {
            $stringReviewType = '';
            foreach ($all as $key => $review) {
                $stringReviewType .= 's.reviewType = :type' . $key . ' OR ';
            }

            $stringReviewType = rtrim($stringReviewType, ' OR ');

            $getReviews = $getReviews->andWhere($stringReviewType);
            foreach ($all as $key => $review) {
                $getReviews = $getReviews->setParameter('type' . $key, $review);
            }
        }

        $getReviews = $getReviews->setParameter('ser', $serial)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (empty($getReviews)) {
            return Http::status(204);
        }

        $returnStructure = [
            'overallRating' => [
                'rating'     => 0,
                'oneStars'   => 0,
                'twoStars'   => 0,
                'threeStars' => 0,
                'fourStars'  => 0,
                'fiveStars'  => 0,
                'ratings'    => 0
            ],
            'mostRecent'    => [],
            'timeline'      => []
        ];

        foreach ($getReviews as $review) {
            if (!isset($returnStructure['timeline'][$review['reviewType']])) {
                $returnStructure['timeline'][$review['reviewType']] = [];
            }

            if (is_null($review['createdAt'])) {
                continue;
            }

            $date = $review['createdAt']->format('Y-m-d H:i:s');
            if (!isset($returnStructure['timeline'][$review['reviewType']][$date])) {
                $returnStructure['timeline'][$review['reviewType']][$date] = [
                    'overallRating' => 0,
                    'ratings'       => [
                        'oneStars'   => 0,
                        'twoStars'   => 0,
                        'threeStars' => 0,
                        'fourStars'  => 0,
                        'fiveStars'  => 0
                    ]
                ];
            }

            $returnStructure['timeline'][$review['reviewType']][$date]['overallRating'] = $review['overallRating'];

            if (!isset($returnStructure['mostRecent'][$review['reviewType']])) {
                $returnStructure['mostRecent'][$review['reviewType']] = $review['overallRating'];
            }

            if (!is_null($review['oneStarRatings'])) {
                $returnStructure['timeline'][$review['reviewType']][$date]['ratings']['oneStars'] += $review['oneStarRatings'];
                $returnStructure['overallRating']['oneStars']                                     += $review['oneStarRatings'];
                $returnStructure['overallRating']['ratings']                                      += $review['oneStarRatings'];
            }

            if (!is_null($review['twoStarRatings'])) {
                $returnStructure['timeline'][$review['reviewType']][$date]['ratings']['twoStars'] += $review['twoStarRatings'];
                $returnStructure['overallRating']['twoStars']                                     += $review['twoStarRatings'];
                $returnStructure['overallRating']['ratings']                                      += $review['twoStarRatings'];
            }

            if (!is_null($review['threeStarRatings'])) {
                $returnStructure['timeline'][$review['reviewType']][$date]['ratings']['threeStars'] += $review['threeStarRatings'];
                $returnStructure['overallRating']['threeStars']                                     += $review['threeStarRatings'];
                $returnStructure['overallRating']['ratings']                                        += $review['threeStarRatings'];
            }

            if (!is_null($review['fourStarRatings'])) {
                $returnStructure['timeline'][$review['reviewType']][$date]['ratings']['fourStars'] += $review['fourStarRatings'];
                $returnStructure['overallRating']['fourStars']                                     += $review['fourStarRatings'];
                $returnStructure['overallRating']['ratings']                                       += $review['fourStarRatings'];
            }

            if (!is_null($review['fiveStarRatings'])) {
                $returnStructure['timeline'][$review['reviewType']][$date]['ratings']['fiveStars'] += $review['fiveStarRatings'];
                $returnStructure['overallRating']['fiveStars']                                     += $review['fiveStarRatings'];
                $returnStructure['overallRating']['ratings']                                       += $review['fiveStarRatings'];
            }
        }

        $sumRatings = (($returnStructure['overallRating']['oneStars'] * 1) +
            ($returnStructure['overallRating']['twoStars'] * 2) + ($returnStructure['overallRating']['threeStars'] * 3) +
            ($returnStructure['overallRating']['fourStars'] * 4) + ($returnStructure['overallRating']['fiveStars'] * 5));
        if ($sumRatings > 0) {
            $returnStructure['overallRating']['rating'] = round($sumRatings /
                $returnStructure['overallRating']['ratings'], 2);
        }

        return Http::status(200, $returnStructure);
    }
}