<?php


namespace App\Package\DataSources;


use App\Controllers\Integrations\Mail\MailSender;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationSettingsService;

class EmailingProfileInteractionFactory
{
    /**
     * @var MailSender $mailSender
     */
    private $mailSender;

    /**
     * @var LocationService $locationService
     */
    private $locationService;

    /**
     * @var OrganizationSettingsService $organizationSettingsService ;
     */
    private $organizationSettingsService;

    /**
     * @var string $apiHost
     */
    private $apiHost;

    /**
     * EmailingProfileInteractionFactory constructor.
     * @param MailSender $mailSender
     * @param LocationService $locationService
     * @param OrganizationSettingsService $organizationSettingsService
     * @param string $apiHost
     */
    public function __construct(
        MailSender $mailSender,
        LocationService $locationService,
        OrganizationSettingsService $organizationSettingsService,
        string $apiHost
    ) {
        $this->mailSender                  = $mailSender;
        $this->locationService             = $locationService;
        $this->organizationSettingsService = $organizationSettingsService;
        $this->apiHost                     = $apiHost;
    }


    /**
     * @param NotifyingProfileInteraction $base
     * @param ProfileInteraction $interaction
     * @return EmailingProfileInteraction
     */
    public function make(
        NotifyingProfileInteraction $base,
        ProfileInteraction $interaction
    ): EmailingProfileInteraction {
        return new EmailingProfileInteraction(
            $base,
            $interaction,
            $this->mailSender,
            $this->locationService,
            $this->organizationSettingsService,
            $this->apiHost
        );
    }
}