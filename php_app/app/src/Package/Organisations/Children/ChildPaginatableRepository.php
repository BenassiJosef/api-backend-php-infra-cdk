<?php


namespace App\Package\Organisations\Children;


use App\Models\Organization;
use App\Package\Database\Database;
use App\Package\Database\PaginatableStatement;
use App\Package\Database\PaginationStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Member\MinimalPresentationOrganization;
use Doctrine\ORM\EntityManager;

class ChildPaginatableRepository implements \App\Package\Response\PaginatableRepository
{
    /**
     * @var Database $database
     */
    private $database;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * ChildPaginatableRepository constructor.
     * @param EntityManager $entityManager
     * @param Organization $organization
     */
    public function __construct(EntityManager $entityManager, Organization $organization)
    {
        $this->database      = new RawStatementExecutor($entityManager);
        $this->entityManager = $entityManager;
        $this->organization  = $organization;
    }


    /**
     * @inheritDoc
     */
    public function count(array $query = []): int
    {
        return $this
            ->database
            ->fetchSingleIntResult(
                new OrganizationChildrenStatement(
                    $this->organization,
                    true
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array
    {
        $statement = new OrganizationChildrenStatement(
            $this->organization
        );
        if (array_key_exists('search', $query)) {
            $statement->setSearchTerm($query['search']);
        }
        $organizationIds = $this
            ->database
            ->fetchFirstColumn(
                new PaginationStatement(
                    $statement,
                    $limit,
                    $offset
                )
            );
        $organizations = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findBy(
                [
                    'id' => $organizationIds,
                ]
            );

        return from($organizations)
            ->select(
                function (Organization $organization): MinimalPresentationOrganization {
                    return new MinimalPresentationOrganization($organization);
                }
            )
            ->toArray();
    }
}