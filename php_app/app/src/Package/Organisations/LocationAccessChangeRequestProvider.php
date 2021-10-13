<?php


namespace App\Package\Organisations;


use App\Models\OauthUser;
use App\Models\Role;
use App\Package\RequestUser\UserProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Exception;

/**
 * Class LocationAccessChangeRequestProvider
 * @package App\Package\Organisations
 */
class LocationAccessChangeRequestProvider
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * LocationAccessChangeRequestProvider constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userProvider  = new UserProvider($entityManager);
    }


    /**
     * @param Request $request
     * @param OauthUser $subject
     * @return LocationAccessChangeRequest
     * @throws Exception
     */
    public function make(Request $request, OauthUser $subject): LocationAccessChangeRequest
    {
        $legacyRoleId = $request->getParsedBodyParam("role", Role::InvalidLegacyId);
        return new LocationAccessChangeRequest(
            $this->userProvider->getOauthUser($request),
            $subject,
            $this->fetchRoleByLegacyId($legacyRoleId),
            $request->getParsedBodyParam("access", [])
        );
    }

    /**
     * @param int $legacyId
     * @return Role
     * @throws Exception
     */
    private function fetchRoleByLegacyId(int $legacyId): Role
    {
        $criteria       = [
            "legacyId"       => $legacyId,
            "organizationId" => null
        ];
        $roleRepository = $this
            ->entityManager
            ->getRepository(Role::class);

        /** @var Role | null $role */
        $role = $roleRepository->findOneBy($criteria);
        if ($role === null) {
            throw new Exception("could not find role for legacyId ($legacyId)");
        }
        return $role;
    }
}