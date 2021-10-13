<?php


namespace App\Package\Loyalty\StampScheme;

use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Database\BaseStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Database\RowFetcher;
use App\Package\DataSources\StatementExecutor;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Marketing\Event;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use JsonSerializable;

class OrganizationStampScheme implements JsonSerializable
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var RowFetcher $rowFetcher
     */
    private $rowFetcher;

    /**
     * @var LoyaltyStampScheme
     */
    private $stampScheme;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * OrganizationStampScheme constructor.
     * @param EntityManager $entityManager
     * @param LoyaltyStampScheme $stampScheme
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        LoyaltyStampScheme $stampScheme,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->entityManager = $entityManager;
        $this->stampScheme   = $stampScheme;
        $this->rowFetcher    = new RawStatementExecutor($entityManager);
        $this->eventNotifier = $eventNotifier;
    }

    /**
     * @param UserProfile $profile
     * @return LazySchemeUser
     */
    public function schemeUser(UserProfile $profile): LazySchemeUser
    {
        return new LazySchemeUser(
            $this->entityManager,
            $this->stampScheme,
            $profile,
            $this->eventNotifier
        );
    }

    public function totalUsers(): int
    {
        $query = "SELECT 
    COUNT(DISTINCT up.id) AS total
FROM
    user_profile up
        LEFT JOIN
    loyalty_stamp_card lsc ON up.id = lsc.profile_id
WHERE     
	lsc.id IS NOT NULL
    AND lsc.scheme_id = :schemeId
    AND lsc.redeemed_at IS NULL
    AND lsc.deleted_at IS NULL;";
        $rows  = $this
            ->rowFetcher
            ->fetchAll(
                new BaseStatement(
                    $query,
                    [
                        'schemeId' => $this->stampScheme->getId()->toString(),
                    ],
                )
            );
        return from($rows)
            ->select(
                function (array $row): int {
                    return $row['total'];
                }
            )
            ->firstOrDefault(0);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return StubSchemeUser[]
     * @throws DBALException
     */
    public function users(int $offset = 0, int $limit = 25): array
    {
        $entityManager = $this->entityManager;
        $rows          = $this
            ->rowFetcher
            ->fetchAll(
                new SchemeUsersStatement(
                    $this->stampScheme->getId(),
                    $offset,
                    $limit
                )
            );
        return from($rows)
            ->select(
                function (array $row) use ($entityManager): StubSchemeUser {
                    return StubSchemeUser::fromArray($entityManager, $row, $this->eventNotifier);
                }
            )
            ->toArray();
    }

    public function removeUser(UserProfile $profile, ?OauthUser $deleter = null): void
    {
        $eventNotifier = $this->eventNotifier;
        /** @var LoyaltyStampCard[] $cards */
        $cards = $this
            ->entityManager
            ->getRepository(LoyaltyStampCard::class)
            ->findBy(
                [
                    'schemeId'  => $this->stampScheme->getId(),
                    'profileId' => $profile->getId(),
                    'deletedAt' => null,
                ]
            );
        from($cards)
            ->each(
                function (LoyaltyStampCard $card) use ($deleter, $eventNotifier): void {
                    $card
                        ->setEventNotifier($eventNotifier)
                        ->delete($deleter);
                }
            );
        $this->entityManager->flush();
    }

    /**
     * @return LoyaltyStampScheme
     */
    public function getScheme()
    {
        return $this->stampScheme;
    }

    /**
     * @param array $data
     * @return $this
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(array $data): self
    {
        $this->stampScheme->updateFromArray($data);
        $this->entityManager->flush();
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return $this->stampScheme->jsonSerialize();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(): void
    {
        $this->stampScheme->delete();
        $this->entityManager->flush();
    }
}
