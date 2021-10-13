<?php


namespace App\Package\Loyalty\Events;


use App\Models\DataSources\DataSource;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\ProfileSaver;
use Exception;

class InteractionNotifier implements EventNotifier
{
    /**
     * @var ProfileInteractionFactory $interactionFactory
     */
    private $interactionFactory;

    /**
     * @var DataSource | null $dataSource
     */
    private $dataSource;

    /**
     * @var LoyaltyStampCard[] $cardCache
     */
    private $cardCache = [];

    /**
     * @var LoyaltyStampScheme[] $schemeCache
     */
    private $schemeCache = [];

    /**
     * @var Organization[]
     */
    private $organizationCache = [];

    /**
     * @var UserProfile[]
     */
    private $profileCache = [];

    /**
     * InteractionNotifier constructor.
     * @param ProfileInteractionFactory $interactionFactory
     */
    public function __construct(
        ProfileInteractionFactory $interactionFactory
    ) {
        $this->interactionFactory = $interactionFactory;
    }

    /**
     * @param LoyaltyStampCardEvent ...$events
     */
    public function notify(LoyaltyStampCardEvent ...$events): void
    {
        foreach ($events as $event) {
            $stampEvent = new StampEventMetadata($event);
            if ($stampEvent->isOrganizationStamp()) {
                // Skip the event if it was created by a Stampede
                // user, as the profile was never in venue
                error_log("org-stamp");
                continue;
            }
            $this
                ->profileSaverFromEvent($stampEvent)
                ->saveUserProfile($this->profileFromEvent($event));
        }
    }


    private function profileSaverFromEvent(StampEventMetadata $eventMetadata): ProfileSaver
    {
        $interactionRequest = $this->interactionRequestFromEvent(
            $eventMetadata
        );
        if ($eventMetadata->isSelfStamp()) {
            error_log("self-stamp");
            return $this
                ->interactionFactory
                ->makeEmailingProfileInteraction($interactionRequest);
        }
        error_log("not-self-stamp");
        return $this
            ->interactionFactory
            ->makeNotifyingProfileInteraction($interactionRequest);
    }


    /**
     * @param StampEventMetadata $event
     * @return InteractionRequest
     * @throws Exception
     */
    private function interactionRequestFromEvent(StampEventMetadata $event): InteractionRequest
    {
        $locations = [];
        if ($event->isLocationSelfStamp()) {
            $locations[] = $event->getSerial();
        }
        return new InteractionRequest(
            $this->organizationFromEvent($event->getStampCardEvent()),
            $this->getDataSource(),
            $locations,
            1
        );
    }

    /**
     * @param LoyaltyStampCardEvent $event
     * @return Organization
     */
    private function organizationFromEvent(LoyaltyStampCardEvent $event): Organization
    {
        $card   = $this->cardFromEvent($event);
        $scheme = $this->schemeFromCard($card);
        return $this->organizationFromScheme($scheme);
    }

    /**
     * @param LoyaltyStampCardEvent $event
     * @return LoyaltyStampCard
     */
    private function cardFromEvent(LoyaltyStampCardEvent $event): LoyaltyStampCard
    {
        $cardId = $event->getCardId()->toString();
        if (!array_key_exists($cardId, $this->cardCache)) {
            $this->cardCache[$cardId] = $event->getCard();
        }
        return $this->cardCache[$cardId];
    }

    /**
     * @param LoyaltyStampCard $card
     * @return LoyaltyStampScheme
     */
    private function schemeFromCard(LoyaltyStampCard $card): LoyaltyStampScheme
    {
        $schemeId = $card->getSchemeId()->toString();
        if (!array_key_exists($schemeId, $this->schemeCache)) {
            $this->schemeCache[$schemeId] = $card->getScheme();
        }
        return $this->schemeCache[$schemeId];
    }

    /**
     * @param LoyaltyStampScheme $scheme
     * @return Organization
     */
    private function organizationFromScheme(LoyaltyStampScheme $scheme): Organization
    {
        $organizationId = $scheme->getOrganizationId()->toString();
        if (!array_key_exists($organizationId, $this->organizationCache)) {
            $this->organizationCache[$organizationId] = $scheme->getOrganization();
        }
        return $this->organizationCache[$organizationId];
    }

    /**
     * @param LoyaltyStampCardEvent $event
     * @return UserProfile
     */
    private function profileFromEvent(LoyaltyStampCardEvent $event): UserProfile
    {
        $card = $this->cardFromEvent($event);
        return $this->profileFromCard($card);
    }

    /**
     * @param LoyaltyStampCard $card
     * @return UserProfile
     */
    private function profileFromCard(LoyaltyStampCard $card): UserProfile
    {
        $profileId = $card->getProfileId();
        if (!array_key_exists($profileId, $this->profileCache)) {
            $this->profileCache[$profileId] = $card->getProfile();
        }
        return $this->profileCache[$profileId];
    }

    /**
     * @return DataSource
     * @throws Exception
     */
    private function getDataSource(): DataSource
    {
        if ($this->dataSource === null) {
            $this->dataSource = $this
                ->interactionFactory
                ->getDataSource('loyalty-stamp');
        }
        return $this->dataSource;
    }
}