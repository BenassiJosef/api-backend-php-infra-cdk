<?php


namespace App\Package\Loyalty\Presentation;


use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\UserProfile;
use App\Package\Loyalty\OrganizationLoyaltyServiceFactory;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Loyalty\StampScheme\LazySchemeUser;
use App\Package\Loyalty\StampScheme\OrganizationStampScheme;
use App\Package\Loyalty\StampScheme\SchemeNotFoundException;
use App\Package\Loyalty\StampScheme\SchemeSecondaryId;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\SimplePaginatedResponse;
use App\Package\RequestUser\UserProvider;
use App\Package\Response\ProblemResponse;
use App\Package\Response\ResponseFactory;
use App\Utils\Http;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;
use function Symfony\Component\String\u;

class OrganizationStampCardController
{
    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * @var OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory
     */
    private $organizationLoyaltyServiceFactory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * OrganizationStampCardController constructor.
     * @param OrganizationProvider $organizationProvider
     * @param OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory
     * @param EntityManager $entityManager
     * @param UserProvider $userProvider
     */
    public function __construct(
        OrganizationProvider $organizationProvider,
        OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory,
        EntityManager $entityManager,
        UserProvider $userProvider
    ) {
        $this->organizationProvider              = $organizationProvider;
        $this->organizationLoyaltyServiceFactory = $organizationLoyaltyServiceFactory;
        $this->entityManager                     = $entityManager;
        $this->userProvider                      = $userProvider;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function createScheme(Request $request, Response $response): Response
    {
        $organization = $this
            ->organizationProvider
            ->organizationForRequest($request);
        try {
            $scheme = $this
                ->organizationLoyaltyServiceFactory
                ->make($organization)
                ->createStampScheme($request->getParsedBody());
        } catch (Throwable $throwable) {
            return $response->withJson(Http::status(400, "Could not create stamp card"), 400);
        }
        return $response->withJson($scheme);
    }

    /**
     * @param Request $request
     * @return OrganizationStampScheme
     * @throws SchemeNotFoundException
     */
    private function organizationStampSchemeFromRequest(Request $request): OrganizationStampScheme
    {
        $organization   = $this
            ->organizationProvider
            ->organizationForRequest($request);
        $schemeIdString = $request
            ->getAttribute('schemeId');
        $schemeId       = Uuid::fromString($schemeIdString);
        return $this
            ->organizationLoyaltyServiceFactory
            ->make($organization)
            ->getOrganizationStampScheme($schemeId);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     */
    public function getScheme(Request $request, Response $response): Response
    {
        $scheme = $this->organizationStampSchemeFromRequest($request);
        return $response->withJson($scheme);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SchemeNotFoundException
     */
    public function updateScheme(Request $request, Response $response): Response
    {
        $scheme = $this->organizationStampSchemeFromRequest($request);
        $scheme->update($request->getParsedBody());
        return $response->withJson($scheme);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getSchemes(Request $request, Response $response): Response
    {
        $organization = $this
            ->organizationProvider
            ->organizationForRequest($request);
        $schemes      = $this
            ->organizationLoyaltyServiceFactory
            ->make($organization)
            ->getStampSchemes();

        return $response->withJson($schemes);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     * @throws DBALException
     */
    public function getSchemeUsers(Request $request, Response $response): Response
    {
        $offset = $request->getQueryParam('offset', 0);
        $limit  = $request->getQueryParam('limit', 25);
        $scheme = $this
            ->organizationStampSchemeFromRequest($request);

        $schemeUsers = $scheme
            ->users(
                $offset,
                $limit
            );
        return $response->withJson(
            new SimplePaginatedResponse(
                $schemeUsers,
                $offset,
                $limit,
                $scheme->totalUsers()
            )
        );
    }

    /**
     * @param Request $request
     * @return UserProfile
     * @throws Exception
     */
    private function userProfileFromRequest(Request $request): UserProfile
    {
        $profileIdString = $request->getAttribute('profileId');
        $profileId       = (int)$profileIdString;
        /** @var UserProfile | null $userProfile */
        $userProfile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->find($profileId);
        if ($userProfile === null) {
            throw new Exception('profile not found');
        }
        return $userProfile;
    }

    /**
     * @param Request $request
     * @return LazySchemeUser
     * @throws SchemeNotFoundException
     * @throws Exception
     */
    private function schemeUserFromRequest(Request $request): LazySchemeUser
    {
        $userProfile = $this->userProfileFromRequest($request);
        return $this
            ->organizationStampSchemeFromRequest($request)
            ->schemeUser($userProfile);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     */
    public function getSchemeUser(Request $request, Response $response): Response
    {
        $schemeUser = $this->schemeUserFromRequest($request);
        return $response->withJson($schemeUser);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     * @throws Throwable
     * @throws AlreadyRedeemedException
     * @throws FullCardException
     * @throws OverstampedCardException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function giveUserStamps(Request $request, Response $response): Response
    {
        $stamper    = $this
            ->userProvider
            ->getOauthUser($request);
        $stamps     = $request->getParsedBodyParam('stamps', 1);
        $schemeUser = $this->schemeUserFromRequest($request);
        $schemeUser->stamp(StampContext::organizationStamp($stamper), $stamps);
        return $response->withJson($schemeUser);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SchemeNotFoundException
     * @throws TransactionRequiredException
     */
    public function redeemUsersReward(Request $request, Response $response): Response
    {
        $redeemer = $this
            ->userProvider
            ->getOauthUser($request);
        $rewardId = Uuid::fromString($request->getAttribute('rewardId'));
        $reward   = $this
            ->schemeUserFromRequest($request)
            ->getReward($rewardId)
            ->redeem($redeemer);
        return $response->withJson($reward);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     */
    public function getSecondaryIds(Request $request, Response $response): Response
    {
        $scheme      = $this->organizationStampSchemeFromRequest($request);
        $secondaryId = new SchemeSecondaryId($this->entityManager, $scheme->getScheme());
        return $response->withJson($secondaryId->getSecondaryIds());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     */
    public function createSecondaryId(Request $request, Response $response): Response
    {
        $scheme = $this->organizationStampSchemeFromRequest($request);

        $secondaryService = new SchemeSecondaryId($this->entityManager, $scheme->getScheme());
        $secondary        = $secondaryService->create($request->getParsedBody());
        return $response->withJson($secondary);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SchemeNotFoundException
     */
    public function updateSecondaryId(Request $request, Response $response): Response
    {
        $id     = $request->getAttribute('id');
        $active = $request->getParsedBodyParam('active', false);
        $serial = $request->getParsedBodyParam('serial', null);
        $scheme = $this->organizationStampSchemeFromRequest($request);

        $secondaryId = new SchemeSecondaryId($this->entityManager, $scheme->getScheme());
        $update      = $secondaryId->update($id, $active, $serial);


        return $response->withJson($update);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteScheme(Request $request, Response $response): Response
    {
        try {
            $this
                ->organizationStampSchemeFromRequest($request)
                ->delete();
        } catch (SchemeNotFoundException $exception) {
            return ResponseFactory::response(
                $response,
                ProblemResponse::fromStatus(
                    StatusCodes::HTTP_NOT_FOUND,
                    $exception->getMessage()
                )
            );
        } catch (Throwable $exception) {
            return ResponseFactory::internalServerError($response);
        }
        return $response->withStatus(
            StatusCodes::HTTP_NO_CONTENT
        );
    }

    public function removeUserFromScheme(Request $request, Response $response): Response
    {
        try {
            $this->organizationStampSchemeFromRequest($request)
                 ->removeUser(
                     $this->userProfileFromRequest($request),
                     $this->userProvider->getOauthUser($request)
                 );
        } catch (Throwable $exception) {
            return ResponseFactory::internalServerError($response);
        }
        return $response->withStatus(StatusCodes::HTTP_NO_CONTENT);
    }
}
