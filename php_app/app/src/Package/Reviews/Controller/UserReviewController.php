<?php

namespace App\Package\Reviews\Controller;

use App\Models\Reviews\ReviewSettings;
use App\Models\UserProfile;
use App\Package\Organisations\OrganizationProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Package\Reviews\ReviewService;
use StampedeTests\app\src\Package\Exceptions\NotFoundException;

class UserReviewController
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var ReviewService $reviewService
     */
    private $reviewService;

    /**
     * ProfileChecker constructor.
     * @param EntityManager $entityManager
     * @param ReviewService $reviewService
     */
    public function __construct(
        EntityManager $entityManager,
        ReviewService $reviewService
    ) {
        $this->entityManager        = $entityManager;
        $this->reviewService        = $reviewService;
    }

    public function getReview(Request $request, Response $response): Response
    {

        return $response->withJson($this->reviewService->getReviews($request));
    }

    public function createReview(Request $request, Response $response): Response
    {
        /**
         * @var ReviewSettings $reviewSettings
         */
        $reviewSettings = $this->reviewService->getSettingsFromRequest($request);

        $rating    = (int)$request->getParsedBodyParam('rating', null);
        $review    = $request->getParsedBodyParam('review', '');
        $metadata  = $request->getParsedBodyParam('metadata', []);
        $platform  = $request->getParsedBodyParam('platform', 'stampede');
        $profileId = (int)$request->getParsedBodyParam('profileId', null);

        /**
         * @var UserProfile $userProfile
         */
        $userProfile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->find($profileId);

        $userReview = $this->reviewService->createReview(
            $reviewSettings,
            $review,
            $rating,
            $platform,
            $metadata,
            $userProfile
        );


        return $response->withJson($userReview);
    }

    public function updateReview(Request $request, Response $response): Response
    {
        return $response->withJson($this->reviewService->updateReview($request));
    }

    public function getReviews(Request $request, Response $response): Response
    {
        $reviews = $this->reviewService->getReviews($request);
        return $response->withJson($reviews);
    }
}
