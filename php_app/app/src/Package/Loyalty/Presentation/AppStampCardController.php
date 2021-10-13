<?php


namespace App\Package\Loyalty\Presentation;


use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Models\Loyalty\LoyaltySecondary;
use App\Package\Loyalty\Stamps\StampContextFactory;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use App\Package\Loyalty\ProfileLoyaltyService;
use App\Package\Loyalty\ProfileLoyaltyServiceFactory;
use App\Package\Pagination\SimplePaginatedResponse;
use App\Package\Profile\UserProfileProvider;
use App\Package\Response\BodyResponse;
use App\Package\Response\ResponseFactory;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

class AppStampCardController
{
    /**
     * @var UserProfileProvider
     */
    private $userProfileProvider;

    /**
     * @var ProfileLoyaltyServiceFactory $profileLoyaltyServiceFactory
     */
    private $profileLoyaltyServiceFactory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var StampContextFactory
     */
    private $stampContextFactory;

    /**
     * AppStampCardController constructor.
     * @param UserProfileProvider $userProfileProvider
     * @param ProfileLoyaltyServiceFactory $profileLoyaltyServiceFactory
     * @param EntityManager $entityManager
     * @param StampContextFactory $stampContextFactory
     */
    public function __construct(
        UserProfileProvider $userProfileProvider,
        ProfileLoyaltyServiceFactory $profileLoyaltyServiceFactory,
        EntityManager $entityManager,
        StampContextFactory $stampContextFactory
    ) {
        $this->userProfileProvider          = $userProfileProvider;
        $this->profileLoyaltyServiceFactory = $profileLoyaltyServiceFactory;
        $this->entityManager                = $entityManager;
        $this->stampContextFactory          = $stampContextFactory;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function getCards(Request $request, Response $response): Response
    {
        $offset  = $request->getQueryParam('offset', 0);
        $limit   = $request->getQueryParam('limit', 25);
        $profile = $this->userProfileProvider->userProfileFromRequest($request);

        $profileLoyaltyService = $this->profileLoyaltyServiceFactory->make($profile);

        $schemes = $profileLoyaltyService
            ->getLoyaltySchemes($offset, $limit);
        return $response->withJson(
            new SimplePaginatedResponse(
                $schemes,
                $offset,
                $limit,
                $profileLoyaltyService->getTotalSchemes()
            )
        );
    }

    private function getSecondaryIdFromRequest(Request $request): ?LoyaltySecondary
    {
        $secondaryId = $request->getAttribute('secondaryId');
        /** @var LoyaltySecondary $secondary */
        $secondary = $this
            ->entityManager
            ->getRepository(LoyaltySecondary::class)
            ->findOneBy(
                [
                    'id'        => $secondaryId,
                    'deletedAt' => null,
                ]
            );
        return $secondary;
    }

    private function profileLoyaltyServiceFromRequest(Request $request): ProfileLoyaltyService
    {
        $profile = $this->userProfileProvider->userProfileFromRequest($request);
        return $this->profileLoyaltyServiceFactory->make($profile);
    }

    public function stamp(Request $request, Response $response): Response
    {
        $secondaryId = $request->getAttribute('secondaryId');
        $context     = $this
            ->stampContextFactory
            ->fromSecondaryIdString($secondaryId);
        $secondary   = $context->getLoyaltySecondary();
        $scheme      = $this
            ->profileLoyaltyServiceFromRequest($request)
            ->getLoyaltyScheme($secondary->getSchemeId());
        try {
            $scheme->stamp($context);
            $secondary->touch();
            $this->entityManager->flush();
        } catch (StampedTooRecentlyException $exception) {
            return ResponseFactory::response(
                $response,
                BodyResponse::fromStatusAndBody(
                    StatusCodes::HTTP_BAD_REQUEST,
                    'STAMPED_TOO_RECENTLY',
                    $scheme
                )
            );
        } catch (Throwable $exception) {
            if (extension_loaded('newrelic')) {
                newrelic_notice_error($exception);
            }
            return ResponseFactory::internalServerError($response);
        }
        return $response->withJson($scheme);
    }

    public function getScheme(Request $request, Response $response): Response
    {
        $secondary = $this->getSecondaryIdFromRequest($request);
        if ($secondary === null) {
            return $response->withJson(Http::status(404, 'scheme not found'), 404);
        }
        $scheme = $this
            ->profileLoyaltyServiceFromRequest($request)
            ->getLoyaltyScheme($secondary->getSchemeId());
        return $response->withJson($scheme);
    }

    private function schemeIdFromRequest(Request $request): UuidInterface
    {
        $schemeIdString = $request->getAttribute('schemeId');
        return Uuid::fromString($schemeIdString);
    }

    private function rewardIdFromRequest(Request $request): UuidInterface
    {
        $rewardIdString = $request->getAttribute('rewardId');
        return Uuid::fromString($rewardIdString);
    }

    public function redeemReward(Request $request, Response $response): Response
    {
        $schemeId       = $this->schemeIdFromRequest($request);
        $profileService = $this->profileLoyaltyServiceFromRequest($request);
        $scheme         = $profileService->getLoyaltyScheme($schemeId);
        if ($scheme === null) {
            return $response->withJson(Http::status(404, 'scheme-not-found'), 404);
        }
        $rewardId = $this->rewardIdFromRequest($request);
        $reward   = $scheme->getReward($rewardId);
        if ($reward === null) {
            return $response->withJson(Http::status(404, 'reward-not-found'), 404);
        }

        try {
            $reward->redeem();
        } catch (AlreadyRedeemedException $exception) {
            return $response->withJson(Http::status(400, 'reward-already-redeemed'), 400);
        } catch (Throwable $exception) {
            return $response->withJson(Http::status(500, 'internal-server-error'), 500);
        }

        return $response->withJson($profileService->getLoyaltyScheme($schemeId));
    }

    public function removeScheme(Request $request, Response $response): Response
    {
        try {
            $this
                ->profileLoyaltyServiceFromRequest($request)
                ->removeLoyaltyScheme(
                    $this->schemeIdFromRequest($request)
                );
        } catch (Throwable $exception) {
            return ResponseFactory::internalServerError($response);
        }
        return $response->withStatus(StatusCodes::HTTP_NO_CONTENT);
    }
}
