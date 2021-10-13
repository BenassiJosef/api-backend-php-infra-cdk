<?php


namespace App\Package\Organisations\Locations;

use App\Models\Locations\LocationSettings;
use App\Models\OauthUser;


use App\Package\Database\Database;
use App\Package\Database\PaginationStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Member\MinimalPresentationLocation;

use App\Package\Organisations\Locations\LocationIdsByUserAndSearch;

use App\Package\Response\PaginatableRepository;
use Doctrine\ORM\EntityManager;

class UserLocationAccessRepository implements PaginatableRepository
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
        $statement = new LocationIdsByUserAndSearch(
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
        $statement = new LocationIdsByUserAndSearch(
            $this->oauthUser
        );

        if (array_key_exists('search', $query)) {
            $statement->setSearchTerm($query['search']);
        }

        $locationIds = $this
            ->database
            ->fetchFirstColumn(
                new PaginationStatement(
                    $statement,
                    $limit,
                    $offset
                )
            );

        $locations = $this
            ->entityManager
            ->getRepository(LocationSettings::class)
            ->findBy(
                [
                    'serial' => $locationIds,
                ]
            );


        return from($locations)
            ->select(function (LocationSettings $location): MinimalPresentationLocation {
                return new MinimalPresentationLocation($location);
            })
            ->toArray();
    }
}
