<?php


namespace App\Package\Organisations;


use App\Package\RequestUser\UserProvider;
use App\Package\Response\PaginatableRepository;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request as SlimRequest;

/**
 * Class UserOrganizationAccessRepositoryFactory
 * @package App\Package\Organisations
 */
class UserOrganizationAccessRepositoryFactory implements \App\Package\Response\PaginatableRepositoryProvider
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
     * UserOrganizationAccessRepositoryFactory constructor.
     * @param EntityManager $entityManager
     * @param UserProvider $userProvider
     */
    public function __construct(EntityManager $entityManager, UserProvider $userProvider)
    {
        $this->entityManager = $entityManager;
        $this->userProvider  = $userProvider;
    }

    /**
     * @inheritDoc
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return new UserOrganizationAccessRepository(
            $this->entityManager,
            $this->userProvider->getOauthUser($request)
        );
    }
}
