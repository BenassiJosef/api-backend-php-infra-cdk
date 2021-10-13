<?php


namespace App\Package\DataSources\Hooks;


use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionSerial;
use App\Models\Locations\LocationSettings;
use App\Models\Loyalty\Exceptions\AlreadyActivatedException;
use App\Models\Loyalty\Exceptions\AlreadyRedeemedException;
use App\Models\Loyalty\Exceptions\FullCardException;
use App\Models\Loyalty\Exceptions\NegativeStampException;
use App\Models\Loyalty\Exceptions\OverstampedCardException;
use App\Models\Loyalty\Exceptions\StampedTooRecentlyException;
use App\Package\Database\BaseStatement;
use App\Package\Database\RawStatementExecutor;
use App\Package\Database\RowFetcher;
use App\Package\Loyalty\OrganizationLoyaltyServiceFactory;
use App\Package\Loyalty\Stamps\StampContext;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Throwable;

class AutoStampingHook implements Hook
{
    /**
     * @var OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory
     */
    private $organizationLoyaltyServiceFactory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var RowFetcher $rowFetcher
     */
    private $rowFetcher;

    /**
     * AutoStampingHook constructor.
     * @param OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory
     * @param EntityManager $entityManager
     */
    public function __construct(
        OrganizationLoyaltyServiceFactory $organizationLoyaltyServiceFactory,
        EntityManager $entityManager
    ) {
        $this->organizationLoyaltyServiceFactory = $organizationLoyaltyServiceFactory;
        $this->entityManager                     = $entityManager;
        $this->rowFetcher                        = new RawStatementExecutor($entityManager);
    }

    /**
     * @param Payload $payload
     * @throws AlreadyActivatedException
     * @throws AlreadyRedeemedException
     * @throws FullCardException
     * @throws NegativeStampException
     * @throws OverstampedCardException
     * @throws StampedTooRecentlyException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function notify(Payload $payload): void
    {
        $dataSource = $payload->getDataSource();
        if ($dataSource->getKey() === 'loyalty-stamp') {
            return; // lets not create a stamp loop
        }
        if (!$dataSource->isVisit()) {
            return; // only auto-stamp if they're visiting the venue
        }
        $serials = $this->serialsFromPayload($payload);
        if (count($serials) === 0) {
            return; // we need to know which location they're at
        }
        $schemes = $this
            ->organizationLoyaltyServiceFactory
            ->make($payload->getInteraction()->getOrganization())
            ->getDefaultStampSchemes($serials);
        foreach ($schemes as $scheme) {
            $schemeUser = $scheme
                ->schemeUser($payload->getUserProfile());
            $canStamp   = $schemeUser
                ->currentCard()
                ->canStampAtThisTime();
            if (!$canStamp) {
                continue; // don't stamp em if they've been stamped recently
            }
            $schemeUser
                ->stamp(
                    StampContext::autoStamp(
                        $dataSource,
                        $this->getLocationSettingsFromPayload($payload)
                    )
                );
        }
    }


    /**
     * @param Payload $payload
     * @return string[]
     * @throws DBALException
     */
    private function serialsFromPayload(Payload $payload): array
    {
        $serialRows = $this
            ->rowFetcher
            ->fetchAll(
                new BaseStatement(
                    "SELECT serial FROM interaction_serial WHERE interaction_id = :interactionId;",
                    [
                        'interactionId' => $payload->getInteraction()->getId()->toString(),
                    ]
                )
            );
        return from($serialRows)
            ->select(
                function (array $row): string {
                    return $row['serial'];
                }
            )
            ->toArray();
    }

    private function getLocationSettingsFromPayload(Payload $payload): ?LocationSettings
    {
        $entityManager = $this->entityManager;
        return from($this->serialsFromPayload($payload))
            ->select(
                function (string $serial) use ($entityManager): LocationSettings {
                    /** @var LocationSettings $locationSettings */
                    $locationSettings = $entityManager
                        ->getRepository(LocationSettings::class)
                        ->findOneBy(
                            [
                                'serial' => $serial,
                            ]
                        );
                    return $locationSettings;
                }
            )
            ->firstOrDefault();

    }
}