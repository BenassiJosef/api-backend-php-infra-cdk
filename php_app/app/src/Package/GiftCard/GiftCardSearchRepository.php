<?php

namespace App\Package\GiftCard;

use App\Models\GiftCard;
use App\Models\Organization;
use App\Package\Database\Database;
use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use App\Package\Database\PaginatableStatement;
use App\Package\GiftCard\Exceptions\InvalidStatusException;
use App\Package\Response\PaginatableRepository;
use Doctrine\ORM\EntityManager;

class GiftCardSearchRepository implements PaginatableRepository
{

    /**
     * @param string $status
     * @throws InvalidStatusException
     */
    public static function validateStatus(string $status): void
    {
        if (!in_array($status, GiftCard::availableStatuses())) {
            throw new InvalidStatusException($status);
        }
    }

    private static $statusLogicMap = [
        GiftCard::STATUS_ACTIVE   => 'AND gc.redeemed_at IS NULL
            AND gc.refunded_at IS NULL
            AND gc.activated_at IS NOT NULL',
        GiftCard::STATUS_UNPAID   => 'AND gc.redeemed_at IS NULL
            AND gc.refunded_at IS NULL
            AND gc.activated_at IS NULL',
        GiftCard::STATUS_REDEEMED => 'AND gc.redeemed_at IS NOT NULL
            AND gc.refunded_at IS NULL',
        GiftCard::STATUS_REFUNDED => 'AND gc.refunded_at IS NOT NULL',
    ];

    private static $dbOrderByMap = [
        GiftCard::STATUS_ACTIVE   => 'activated_at',
        GiftCard::STATUS_UNPAID   => 'created_at',
        GiftCard::STATUS_REDEEMED => 'redeemed_at',
        GiftCard::STATUS_REFUNDED => 'refunded_at',
    ];

    private static $doctrineOrderByMap = [
        GiftCard::STATUS_ACTIVE   => 'activatedAt',
        GiftCard::STATUS_UNPAID   => 'createdAt',
        GiftCard::STATUS_REDEEMED => 'redeemedAt',
        GiftCard::STATUS_REFUNDED => 'refundedAt',
    ];

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Database $database
     */
    private $database;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var string | null $searchTerm
     */
    private $searchTerm;

    /**
     * GiftCardSearchRepository constructor.
     * @param EntityManager $entityManager
     * @param Database $database
     * @param Organization $organization
     * @param string | null $searchTerm
     */
    public function __construct(
        EntityManager $entityManager,
        Database $database,
        Organization $organization,
        ?string $searchTerm = null
    ) {
        $this->entityManager = $entityManager;
        $this->database      = $database;
        $this->organization  = $organization;
        $this->searchTerm    = $searchTerm;
    }


    /**
     * @param array $query
     * @return int
     */
    public function count(array $query = []): int
    {
        return $this
            ->database
            ->fetchSingleIntResult(
                $this->statement($query)->countStatement()
            );
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param array $query
     * @return GiftCard[]
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function fetchAll(int $offset = 0, int $limit = 25, array $query = []): array
    {
        return $this
            ->entityManager
            ->getRepository(GiftCard::class)
            ->findBy(
                [
                    'id' => $this
                        ->database
                        ->fetchFirstColumn(
                            $this
                                ->statement($query)
                                ->withPagination($limit, $offset)
                        ),
                ],
                $this->doctrineOrderBy($query)
            );
    }

    private function getSearchTerm(): string
    {
        $searchTerm = $this->searchTerm;
        return "%${searchTerm}%";
    }

    private function dbOrderByColumn(array $query = []): string
    {
        $status = $this->statusFromQuery($query);
        if ($status === null) {
            return 'created_at';
        }
        return self::$dbOrderByMap[$status];
    }

    private function doctrineOrderBy(array $query = []): array
    {
        $status = $this->statusFromQuery($query);
        if ($status === null) {
            return [
                'createdAt' => 'DESC',
            ];
        }
        $orderByProperty = self::$doctrineOrderByMap[$status];
        return [
            $orderByProperty => 'DESC',
        ];
    }

    /**
     * @param array $query
     * @return string|null
     * @throws InvalidStatusException
     */
    private function statusFromQuery(array $query = []): ?string
    {
        if (!array_key_exists('status', $query)) {
            return null;
        }
        $status = $query['status'];
        self::validateStatus($status);
        return $status;
    }


    /**
     * @param array $query
     * @return string
     * @throws InvalidStatusException
     */
    private function logicFromQuery(array $query = []): string
    {
        $status = $this->statusFromQuery($query);
        if ($status === null) {
            return '';
        }
        return self::$statusLogicMap[$status];
    }

    /**
     * @param array $queryParts
     * @return string
     * @throws InvalidStatusException
     */
    private function query(array $queryParts = []): string
    {
        $query = "SELECT
                %s
            FROM user_profile up
            LEFT JOIN gift_card gc ON gc.profile_id = up.id
            WHERE gc.organization_id = :organizationId ";
        $query .= $this->logicFromQuery($queryParts);
        if ($this->searchTerm !== null) {
            return $query . "
            AND (
		            up.first LIKE :term
                    OR up.last LIKE :term
                    OR up.email LIKE :term
            )";
        }
        $orderByColumn = $this->dbOrderByColumn($queryParts);
        return $query . " ORDER BY gc.${orderByColumn} DESC";
    }

    private function parameters(): array
    {
        return array_merge(
            ['organizationId' => $this->organization->getId()->toString()],
            $this->searchParameters()
        );
    }

    private function searchParameters(): array
    {
        if ($this->searchTerm === null) {
            return [];
        }
        return ['term' => $this->getSearchTerm()];
    }

    private function statement(array $query = []): PaginatableStatement
    {
        return new PaginatableStatement(
            ['gc.id'],
            $this->query($query),
            $this->parameters()
        );
    }


}