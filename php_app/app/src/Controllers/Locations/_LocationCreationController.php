<?php

/**
 * Created by PhpStorm.
 * User=> patrickclover
 * Date=> 01/02/2017
 * Time=> 16=>17
 */

namespace App\Controllers\Locations;

use App\Controllers\Locations\Access\LocationsAccessController;
use App\Controllers\Locations\Creation\LocationCreationChecks;
use App\Controllers\Locations\Settings\Bandwidth\_BandwidthController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\Timeouts\_TimeoutsController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Locations\Bandwidth\LocationBandwidth;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationSettings;
use App\Models\Locations\Other\LocationOther;
use App\Models\Locations\Position\LocationPosition;
use App\Models\Locations\Schedule\LocationSchedule;
use App\Models\Locations\Schedule\LocationScheduleDay;
use App\Models\Locations\Social\LocationSocial;
use App\Models\Locations\Timeout\LocationTimeout;
use App\Models\Locations\WiFi\LocationWiFi;
use App\Models\NetworkAccess;
use App\Models\NetworkAccessMembers;
use App\Models\Organization;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;

class _LocationCreationController implements ILocationCreation
{
    protected $em;
    public $locationCreationChecksController;
    public $locationAccessController;
    protected $vendor   = '';
    protected $serial;
    private $isRadius = false;

    protected $dataGeneratedWithinInform = [];

    /**
     * @var OrganizationService
     */
    private $organizationService;

    public function __construct(EntityManager $em)
    {
        $this->em                               = $em;
        $this->locationCreationChecksController = new LocationCreationChecks($this->em, $this->serial, $this->vendor);
        $this->locationAccessController         = new LocationsAccessController($this->em);
        $this->organizationService              = new OrganizationService($em);
    }

    /**
     * @return string
     */

    public function serialGenerator()
    {
        $serial = strtoupper(Strings::random(12));
        $this->setSerial($serial);
        return $serial;
    }

    public function initialiseLocationSettings(string $serial, Organization $organization = null)
    {
        if ($organization === null) {
            $organization = $this->organisationService->getRootOrganisation();
        }


        $schedule = new LocationSchedule();
        $this->em->persist($schedule);

        $settings = new LocationSettings(
            $serial,
            LocationOtherController::defaultOther(),
            BrandingController::defaultBranding(),
            _WiFiController::defaultWiFi($serial),
            LocationPositionController::defaultPosition(),
            new LocationSocial(false, 'facebook', ''),
            $schedule->id,
            LocationSettings::defaultUrl(),
            LocationSettings::defaultFreeQuestions(),
            $organization
        );

        $settings->translation = LocationSettings::defaultTranslation();
        $settings->language    = LocationSettings::defaultLanguage();
        $this->em->persist($settings);

        $freeBandwidth = _BandwidthController::defaultFreeBandwidth($settings->getOtherSettings()->getId());
        $this->em->persist($freeBandwidth);

        $paidBandwidth = _BandwidthController::defaultPaidBandwidth($settings->getOtherSettings()->getId());
        $this->em->persist($paidBandwidth);

        $freeTimeout = _TimeoutsController::defaultFreeTimeout($settings->getOtherSettings()->getId());
        $this->em->persist($freeTimeout);

        $paidTimeout = _TimeoutsController::defaultPaidTimeout($settings->getOtherSettings()->getId());
        $this->em->persist($paidTimeout);

        for ($i = 0; $i <= 6; $i++) {
            $day = new LocationScheduleDay(false, $schedule->id, $i);
            $this->em->persist($day);
        }

        $this->em->flush();
    }

    public function createInform(?string $serial)
    {
        throw new \Exception("MUST_BE_OVERRIDDEN", 501);
    }

    /**
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getVendorForChargeBee()
    {
        return strtoupper($this->vendor);
    }

    public function setVendor(string $vendor)
    {
        $this->vendor = $vendor;
        $this->locationCreationChecksController->setVendor($vendor);
    }

    public function setSerial(?string $serial)
    {
        $this->serial = $serial;
        $this->locationCreationChecksController->setSerial($serial);
    }

    public function getSerial()
    {
        return $this->serial;
    }

    public function setIsRadius(bool $radius)
    {
        $this->isRadius = $radius;
    }

    public function getDataGeneratedWithinInform()
    {
        return $this->dataGeneratedWithinInform;
    }

    public function createBespokeLogic(string $serial, string $vendor)
    {
        throw new \Exception('MUST_BE_OVERRIDDEN', 501);
    }

    public function deleteBespokeLogic(string $serial, string $vendor)
    {
        throw new \Exception('MUST_BE_OVERRIDDEN', 501);
    }

    public function isLocationBeingReactivated(string $serial)
    {
        throw new \Exception('MUST_BE_OVERRIDDEN', 501);
    }
}
