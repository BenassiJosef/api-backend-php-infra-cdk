<?php


namespace App\Package\Loyalty;


use App\Models\UserProfile;
use App\Package\Database\RowFetcher;
use App\Package\Loyalty\Events\EventNotifier;
use App\Package\Loyalty\Events\NopNotifier;
use Doctrine\ORM\EntityManager;

class ProfileLoyaltyServiceFactory
{
    /**
     * @var RowFetcher $rowFetcher
     */
    private $rowFetcher;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var EventNotifier $eventNotifier
     */
    private $eventNotifier;

    /**
     * ProfileLoyaltyServiceFactory constructor.
     * @param RowFetcher $rowFetcher
     * @param EntityManager $entityManager
     * @param EventNotifier|null $eventNotifier
     */
    public function __construct(
        RowFetcher $rowFetcher,
        EntityManager $entityManager,
        ?EventNotifier $eventNotifier = null
    ) {
        if ($eventNotifier === null) {
            $eventNotifier = new NopNotifier();
        }
        $this->rowFetcher    = $rowFetcher;
        $this->entityManager = $entityManager;
        $this->eventNotifier = $eventNotifier;
    }

    /**
     * @param UserProfile $profile
     * @return ProfileLoyaltyService
     */
    public function make(UserProfile $profile): ProfileLoyaltyService
    {
        return new ProfileLoyaltyService(
            $this->entityManager,
            $this->rowFetcher,
            $profile,
            $this->eventNotifier
        );
    }
}