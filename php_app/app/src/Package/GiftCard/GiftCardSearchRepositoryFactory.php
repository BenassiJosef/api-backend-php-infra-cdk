<?php


namespace App\Package\GiftCard;


use App\Models\Organization;
use App\Package\Database\Database;
use App\Package\Database\RawStatementExecutor;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Response\PaginatableRepository;
use App\Package\Response\PaginatableRepositoryProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request as SlimRequest;

/**
 * Class GiftCardSearchRepositoryFactory
 * @package App\Package\GiftCard
 */
class GiftCardSearchRepositoryFactory implements PaginatableRepositoryProvider
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Database $database
     */
    private $database;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * GiftCardSearchRepositoryFactory constructor.
     * @param EntityManager $entityManager
     * @param Database|null $database
     * @param OrganizationProvider|null $organizationProvider
     */
    public function __construct(
        EntityManager $entityManager,
        ?Database $database = null,
        ?OrganizationProvider $organizationProvider = null
    ) {
        if ($database === null) {
            $database = new RawStatementExecutor($entityManager);
        }
        if ($organizationProvider === null) {
            $organizationProvider = new OrganizationProvider($entityManager);
        }
        $this->entityManager        = $entityManager;
        $this->database             = $database;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @inheritDoc
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return $this->make(
            $this->organizationProvider->organizationForRequest($request),
            $request->getQueryParam('term')
        );
    }

    /**
     * @param Organization $organization
     * @param string|null $searchTerm
     * @return GiftCardSearchRepository
     */
    public function make(Organization $organization, ?string $searchTerm = null): GiftCardSearchRepository
    {
        return new GiftCardSearchRepository(
            $this->entityManager,
            $this->database,
            $organization,
            $searchTerm
        );
    }
}