<?php


namespace App\Package\Loyalty\Stamps;


use App\Models\DataSources\DataSource;
use App\Models\Locations\LocationSettings;
use App\Models\Loyalty\LoyaltySecondary;
use App\Models\OauthUser;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Exception;

class StampContextFactory
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * StampContextFactory constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $secondaryUuidString
     * @return StampContext
     * @throws Exception
     */
    public function fromSecondaryIdString(string $secondaryUuidString): StampContext
    {
        return $this->fromSecondaryId(
            Uuid::fromString($secondaryUuidString)
        );
    }

    /**
     * @param UuidInterface $secondaryUuid
     * @return StampContext
     * @throws Exception
     */
    public function fromSecondaryId(UuidInterface $secondaryUuid): StampContext
    {
        /** @var LoyaltySecondary | null $secondaryId */
        $secondaryId = $this
            ->entityManager
            ->getRepository(LoyaltySecondary::class)
            ->findOneBy(
                [
                    'id'        => $secondaryUuid,
                    'deletedAt' => null,
                ]
            );
        if ($secondaryId === null) {
            throw new Exception('cannot find secondary ID');
        }

        if ($secondaryId->getSerial() === null) {
            return StampContext::selfStamp($secondaryId);
        }

        /** @var LocationSettings | null $locationSettings */
        $locationSettings = $this
            ->entityManager
            ->getRepository(LocationSettings::class)
            ->findOneBy(
                [
                    'serial' => $secondaryId->getSerial(),
                ]
            );
        if ($locationSettings === null) {
            throw new Exception('could not find location_settings');
        }

        return StampContext::selfStampWithLocation($secondaryId, $locationSettings);
    }

    public function fromStamper(OauthUser $oauthUser): StampContext
    {
        return StampContext::organizationStamp($oauthUser);
    }
}