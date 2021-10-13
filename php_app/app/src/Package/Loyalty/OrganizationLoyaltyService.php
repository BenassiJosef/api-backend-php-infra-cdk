<?php


namespace App\Package\Loyalty;


use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\Organization;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\StampScheme\OrganizationStampScheme;
use App\Package\Loyalty\StampScheme\SchemeNotFoundException;
use App\Package\Loyalty\StampScheme\StampSchemeFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Ramsey\Uuid\UuidInterface;

class OrganizationLoyaltyService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var StampSchemeFactory $stampSchemeFactory
     */
    private $stampSchemeFactory;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * OrganizationLoyaltyService constructor.
     * @param EntityManager $entityManager
     * @param Organization $organization
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        Organization $organization,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->entityManager      = $entityManager;
        $this->organization       = $organization;
        $this->stampSchemeFactory = StampSchemeFactory::defaultStampSchemeFactory();
        $this->eventNotifier      = $eventNotifier;
    }

    /**
     * @param array $data
     * @return LoyaltyStampScheme
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createStampScheme(array $data): LoyaltyStampScheme
    {
        $scheme = $this->stampSchemeFactory->make($this->organization, $data);
        $this->entityManager->persist($scheme);
        $this->entityManager->flush();
        return $scheme;
    }

    /**
     * @param array $criteria
     * @return OrganizationStampScheme[]
     */
    private function getStampSchemesByCriteria(array $criteria): array
    {
        $entityManager = $this->entityManager;
        $eventNotifier = $this->eventNotifier;
        $rawSchemes    = $this
            ->entityManager
            ->getRepository(LoyaltyStampScheme::class)
            ->findBy($criteria);
        return from($rawSchemes)
            ->select(
                function (LoyaltyStampScheme $scheme) use ($entityManager, $eventNotifier): OrganizationStampScheme {
                    return new OrganizationStampScheme(
                        $entityManager,
                        $scheme,
                        $eventNotifier
                    );
                }
            )
            ->toArray();
    }

    /**
     * @param string[] $serials
     * @return OrganizationStampScheme[]
     */
    public function getDefaultStampSchemes(array $serials = []): array
    {
        $schemes = $this->getStampSchemesByCriteria(
            [
                'organizationId' => $this->organization->getId(),
                'deletedAt'      => null,
                'isActive'       => true,
                'isDefault'      => true,
                'serial'         => $serials,
            ]
        );
        if (count($schemes) > 0) {
            return $schemes;
        }
        return $this->getStampSchemesByCriteria(
            [
                'organizationId' => $this->organization->getId(),
                'deletedAt'      => null,
                'isActive'       => true,
                'isDefault'      => true,
            ]
        );
    }

    /**
     * @return OrganizationStampScheme[]
     */
    public function getStampSchemes(): array
    {
        return $this->getStampSchemesByCriteria(
            [
                'organizationId' => $this->organization->getId(),
                'deletedAt'      => null,
            ]
        );
    }

    /**
     * @param UuidInterface $schemeId
     * @return OrganizationStampScheme
     * @throws SchemeNotFoundException
     */
    public function getOrganizationStampScheme(UuidInterface $schemeId): OrganizationStampScheme
    {
        return new OrganizationStampScheme(
            $this->entityManager,
            $this->getStampScheme($schemeId),
            $this->eventNotifier
        );
    }

    /**
     * @param UuidInterface $schemeId
     * @return LoyaltyStampScheme
     * @throws SchemeNotFoundException
     */
    private function getStampScheme(UuidInterface $schemeId): LoyaltyStampScheme
    {
        /** @var LoyaltyStampScheme | null $loyaltyStampScheme */
        $loyaltyStampScheme = $this
            ->entityManager
            ->getRepository(LoyaltyStampScheme::class)
            ->findOneBy(
                [
                    'id'        => $schemeId,
                    'deletedAt' => null,
                ]
            );

        if ($loyaltyStampScheme === null) {
            throw new SchemeNotFoundException($schemeId);
        }
        return $loyaltyStampScheme;
    }
}