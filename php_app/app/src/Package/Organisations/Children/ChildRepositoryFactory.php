<?php


namespace App\Package\Organisations\Children;


use App\Package\Organisations\OrganizationProvider;
use App\Package\Response\PaginatableRepository;
use App\Package\Response\PaginatableRepositoryProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request as SlimRequest;

class ChildRepositoryFactory implements PaginatableRepositoryProvider
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * ChildRepositoryFactory constructor.
     * @param EntityManager $entityManager
     * @param OrganizationProvider $organizationProvider
     */
    public function __construct(
        EntityManager $entityManager,
        OrganizationProvider $organizationProvider
    ) {
        $this->entityManager        = $entityManager;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param SlimRequest $request
     * @return PaginatableRepository
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return new ChildPaginatableRepository(
            $this->entityManager,
            $this->organizationProvider->organizationForRequest($request)
        );
    }

}