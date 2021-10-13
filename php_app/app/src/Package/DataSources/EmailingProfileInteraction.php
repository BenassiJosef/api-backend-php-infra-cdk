<?php


namespace App\Package\DataSources;


use App\Controllers\Integrations\Mail\MailSender;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\InteractionSerial;
use App\Models\Locations\LocationSettings;
use App\Models\UserProfile;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationSettingsService;
use GuzzleHttp\Psr7\Uri;

class EmailingProfileInteraction implements ProfileSaver
{
    const TEMPLATE = 'CheckoutTemplate';

    /**
     * @var NotifyingProfileInteraction $base
     */
    private $base;

    /**
     * @var ProfileInteraction $profileInteraction
     */
    private $profileInteraction;

    /**
     * @var MailSender $mailSender
     */
    private $mailSender;

    /**
     * @var LocationService $locationService
     */
    private $locationService;

    /**
     * @var OrganizationSettingsService $organizationSettingsService
     */
    private $organizationSettingsService;

    /**
     * @var string $apiHost
     */
    private $apiHost;

    /**
     * EmailingProfileInteraction constructor.
     * @param NotifyingProfileInteraction $base
     * @param ProfileInteraction $profileInteraction
     * @param MailSender $mailSender
     * @param LocationService $locationService
     * @param OrganizationSettingsService $organizationSettingsService
     * @param string $apiHost
     */
    public function __construct(
        NotifyingProfileInteraction $base,
        ProfileInteraction $profileInteraction,
        MailSender $mailSender,
        LocationService $locationService,
        OrganizationSettingsService $organizationSettingsService,
        string $apiHost = "https://api.stampede.ai"
    ) {
        $this->base                        = $base;
        $this->profileInteraction          = $profileInteraction;
        $this->mailSender                  = $mailSender;
        $this->locationService             = $locationService;
        $this->organizationSettingsService = $organizationSettingsService;
        $this->apiHost                     = $apiHost;
    }

    /**
     * @inheritDoc
     */
    public function saveCandidateProfile(CandidateProfile $profile, OptInStatuses $optInStatusesOverride = null)
    {
        $this->base->saveCandidateProfile($profile, $optInStatusesOverride);
        $this->email();
    }

    /**
     * @inheritDoc
     */
    public function saveEmail(string $email, OptInStatuses $optInStatuses = null)
    {
        $this->base->saveEmail($email, $optInStatuses);
        $this->email();
    }

    /**
     * @inheritDoc
     */
    public function saveProfileId(int $profileId)
    {
        $this->base->saveProfileId($profileId);
        $this->email();
    }

    /**
     * @inheritDoc
     */
    public function saveUserProfile(UserProfile $userProfile)
    {
        $this->base->saveUserProfile($userProfile);
        $this->email();
    }

    /**
     * @return LocationSettings[]
     */
    private function getLocations(): array
    {
        $serials = $this->profileInteraction->serials();
        return $this->locationService->getLocationsBySerial($serials);
    }

    /**
     * @return string
     */
    private function link(): string
    {
        $parts = [
            'public',
            'interactions',
            $this->profileInteraction->interactionId(),
            'end'
        ];
        $path  = implode('/', $parts);
        $uri   = new Uri($this->apiHost);
        $uri   = $uri->withPath($path);
        return $uri->__toString();
    }

    private function email()
    {
        $orgId    = $this
            ->profileInteraction
            ->getInteractionRequest()
            ->getOrganization()
            ->getId();
        $emailsOn = $this
            ->organizationSettingsService
            ->settings($orgId)
            ->getSettings()
            ->canSendCheckoutEmail();
        if (!$emailsOn) {
            return;
        }
        $isVisit = $this
            ->profileInteraction
            ->getInteractionRequest()
            ->getDataSource()
            ->isVisit();
        if (!$isVisit) {
            return;
        }

        $profiles  = $this->profileInteraction->profiles();
        $locations = $this->getLocations();
        foreach ($profiles as $profile) {
            foreach ($locations as $location) {
                $alias = $location->getAlias() ?? 'Stampede';
                $this->mailSender->send(
                    [
                        [
                            'to'   => $profile->getEmail(),
                            'name' => $profile->getFullName(),
                        ]
                    ],
                    [
                        'alias' => $alias,
                        'link'  => $this->link(),
                    ],
                    self::TEMPLATE,
                    "Let us know you've left ${alias} (TRACK & TRACE)"
                );
            }
        }
    }

    public function getLastInsertedProfileInformation(): ?SingleProfileResponse
    {
        return $this->base->getLastInsertedProfileInformation();
    }
}