<?php


namespace App\Package\Loyalty;

use App\Models\Organization;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use Doctrine\ORM\EntityManager;

class OrganizationLoyaltyServiceFactory
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * OrganizationLoyaltyServiceFactory constructor.
     * @param EntityManager $entityManager
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->entityManager = $entityManager;
        $this->eventNotifier = $eventNotifier;
    }

    /**
     * @param Organization $organization
     * @return OrganizationLoyaltyService
     */
    public function make(Organization $organization): OrganizationLoyaltyService
    {
        return new OrganizationLoyaltyService(
            $this->entityManager,
            $organization,
            $this->eventNotifier
        );
    }
}
