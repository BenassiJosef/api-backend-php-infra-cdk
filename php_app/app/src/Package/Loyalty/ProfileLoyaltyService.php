<?php


namespace App\Package\Loyalty;


use App\Models\Locations\LocationSettings;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\UserProfile;
use App\Package\Database\BaseStatement;
use App\Package\Database\RowFetcher;
use App\Package\Loyalty\App\AppLoyaltySchemeStatement;
use App\Package\Loyalty\App\LoyaltyBranding;
use App\Package\Loyalty\App\LoyaltyLocation;
use App\Package\Loyalty\App\LoyaltyOrganization;
use App\Package\Loyalty\App\StubAppLoyaltyScheme;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\StampScheme\LazySchemeUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Ramsey\Uuid\UuidInterface;

class ProfileLoyaltyService
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
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * ProfileLoyaltyService constructor.
     * @param EntityManager $entityManager
     * @param RowFetcher $rowFetcher
     * @param UserProfile $userProfile
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        RowFetcher $rowFetcher,
        UserProfile $userProfile,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->entityManager = $entityManager;
        $this->rowFetcher    = $rowFetcher;
        $this->userProfile   = $userProfile;
        $this->eventNotifier = $eventNotifier;
    }

    public function getTotalSchemes(): int
    {
        $query = "SELECT 
	COUNT(DISTINCT lss.id) AS total
    FROM
        `organization` o
    LEFT JOIN loyalty_stamp_scheme lss ON lss.organization_id = o.id
    LEFT JOIN loyalty_stamp_card lsc ON lss.id = lsc.scheme_id
    WHERE lsc.id IS NOT NULL
    AND lsc.profile_id = :userId
    AND lss.deleted_at IS NULL
    AND lsc.deleted_at IS NULL";
        $rows  = $this
            ->rowFetcher
            ->fetchAll(
                new BaseStatement(
                    $query,
                    [
                        'userId' => $this->userProfile->getId(),
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
     * @return StubAppLoyaltyScheme[]
     */
    public function getLoyaltySchemes(int $offset = 0, int $limit = 25): array
    {
        $entityManager = $this->entityManager;
        $eventNotifier = $this->eventNotifier;
        return from($this->getData($offset, $limit))
            ->select(
                function (array $data) use ($entityManager, $eventNotifier): StubAppLoyaltyScheme {
                    return StubAppLoyaltyScheme::fromArray($entityManager, $data, $eventNotifier);
                }
            )
            ->toArray();
    }

    /**
     * @param UuidInterface $schemeId
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeLoyaltyScheme(UuidInterface $schemeId): void
    {
        $eventNotifier = $this->eventNotifier;
        /** @var LoyaltyStampCard[] $cards */
        $cards = $this
            ->entityManager
            ->getRepository(LoyaltyStampCard::class)
            ->findBy(
                [
                    'schemeId'  => $schemeId,
                    'profileId' => $this->userProfile->getId(),
                    'deletedAt' => null,
                ]
            );
        from($cards)
            ->select(
                function (LoyaltyStampCard $loyaltyStampCard) use ($eventNotifier): LoyaltyStampCard {
                    return $loyaltyStampCard->setEventNotifier($eventNotifier);
                }
            )
            ->each(
                function (LoyaltyStampCard $card): void {
                    $card->delete();
                }
            );
        $this->entityManager->flush();
    }

    /**
     * @param UuidInterface $schemeId
     * @return StubAppLoyaltyScheme|null
     */
    public function getLoyaltyScheme(UuidInterface $schemeId): ?StubAppLoyaltyScheme
    {
        /** @var LoyaltyStampScheme $scheme */
        $scheme = $this
            ->entityManager
            ->getRepository(LoyaltyStampScheme::class)
            ->findOneBy(
                [
                    'id'        => $schemeId,
                    'deletedAt' => null,
                ]
            );
        if ($scheme === null) {
            return null;
        }
        $organization = $scheme->getOrganization();
        /** @var LoyaltyLocation[] $locations */
        $locations = from($organization->getLocations())
            ->select(
                function (LocationSettings $locationSettings): LoyaltyLocation {
                    return LoyaltyLocation::fromLocationSettings($locationSettings);
                }
            )
            ->toArray();
        return new StubAppLoyaltyScheme(
            $this->entityManager,
            $schemeId,
            LoyaltyOrganization::fromOrganization($scheme->getOrganization()),
            $locations,
            LoyaltyBranding::fromScheme($scheme),
            new LazySchemeUser(
                $this->entityManager,
                $scheme,
                $this->userProfile,
                $this->eventNotifier
            ),
            $this->eventNotifier
        );
    }

    private function getData(int $offset = 0, int $limit = 25): array
    {
        return $this
            ->rowFetcher
            ->fetchAll(
                new AppLoyaltySchemeStatement(
                    $this->userProfile,
                    $offset,
                    $limit
                )
            );
    }
}