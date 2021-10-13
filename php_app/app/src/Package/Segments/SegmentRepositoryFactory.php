<?php


namespace App\Package\Segments;


use App\Package\Organisations\OrganizationProvider;
use App\Package\Response\PaginatableRepository;
use App\Package\Response\PaginatableRepositoryProvider;
use App\Package\Segments\Database\QueryFactory;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Request as SlimRequest;

class SegmentRepositoryFactory implements PaginatableRepositoryProvider
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var QueryFactory $queryFactory
     */
    private $queryFactory;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * SegmentRepositoryFactory constructor.
     * @param EntityManager $entityManager
     * @param QueryFactory $queryFactory
     * @param OrganizationProvider $organizationProvider
     */
    public function __construct(
        EntityManager $entityManager,
        QueryFactory $queryFactory,
        OrganizationProvider $organizationProvider
    ) {
        $this->entityManager        = $entityManager;
        $this->queryFactory         = $queryFactory;
        $this->organizationProvider = $organizationProvider;
    }


    /**
     * @param SlimRequest $request
     * @return SegmentRepository
     * @throws Exception
     */
    public function segmentRepository(SlimRequest $request): SegmentRepository
    {
        return new SegmentRepository(
            $this->entityManager,
            $this->queryFactory,
            $this
                ->organizationProvider
                ->organizationForRequest($request)
        );
    }

    /**
     * @inheritDoc
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return $this->segmentRepository($request);
    }
}