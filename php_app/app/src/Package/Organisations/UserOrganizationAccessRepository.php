<?php


namespace App\Package\Organisations;


use App\Models\OauthUser;
use App\Models\Organization;
use App\Package\Database\BaseStatement;
use App\Package\Database\Database;
use App\Package\Database\PaginationStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Member\MinimalPresentationOrganization;
use App\Package\Organisations\RBAC\OrganizationIdsByUserAndRolesStatement;
use App\Package\Organisations\RBAC\OrganizationIdsByUserAndSearch;
use App\Package\Response\PaginatableRepository;
use Doctrine\ORM\EntityManager;

class UserOrganizationAccessRepository implements PaginatableRepository
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
     * @var OauthUser $oauthUser
     */
    private $oauthUser;

    /**
     * UserOrganizationAccessRepository constructor.
     * @param EntityManager $entityManager
     * @param OauthUser $oauthUser
     */
    public function __construct(
        EntityManager $entityManager,
        OauthUser $oauthUser
    ) {
        $this->database      = new RawStatementExecutor($entityManager);
        $this->entityManager = $entityManager;
        $this->oauthUser     = $oauthUser;
    }

    /**
     * @param array $query
     * @return int
     */
    public function count(array $query = []): int
    {
        $statement = new OrganizationIdsByUserAndSearch(
            $this->oauthUser,
            true,
        );
        if (array_key_exists('search', $query)) {
            $statement->setSearchTerm($query['search']);
        }
        return $this
            ->database
            ->fetchSingleIntResult(
                $statement
            );
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param array $query
     * @return array
     */
    public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array
    {
        $statement = new OrganizationIdsByUserAndSearch(
            $this->oauthUser
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
            ->select(function (Organization $organization): MinimalPresentationOrganization {
                return new MinimalPresentationOrganization($organization);
            })
            ->toArray();
    }
}