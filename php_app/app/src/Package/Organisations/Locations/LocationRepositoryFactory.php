<?php

namespace App\Package\Organisations\Locations;

use App\Package\Organisations\OrganizationProvider;
use App\Package\RequestUser\UserProvider;
use App\Package\Response\PaginatableRepository;
use App\Package\Response\PaginatableRepositoryProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request as SlimRequest;

class LocationRepositoryFactory implements PaginatableRepositoryProvider
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * ChildRepositoryFactory constructor.
     * @param EntityManager $entityManager
     * @param OrganizationProvider $organizationProvider
     */
    public function __construct(
        EntityManager $entityManager,
        UserProvider $userProvider
    ) {
        $this->entityManager        = $entityManager;
        $this->userProvider = $userProvider;
    }

    /**
     * @param SlimRequest $request
     * @return PaginatableRepository
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return new UserLocationAccessRepository(
            $this->entityManager,
            $this->userProvider->getOauthUser($request)
        );
    }
}
