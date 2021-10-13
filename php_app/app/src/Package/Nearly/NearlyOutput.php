<?php

namespace App\Package\Nearly;

use App\Models\DataSources\OrganizationRegistration;
use App\Models\LocationPlan;
use App\Models\Locations\LocationSettings;
use App\Models\Nearly\Stories\NearlyStory;

use App\Models\UserProfile;
use App\Models\Reviews\ReviewSettings;

use JsonSerializable;

class NearlyOutput implements JsonSerializable
{

    private $paidQuestionsKeys = [
        'Email'     => 'email',
        'Firstname' => 'first',
        'Lastname'  => 'last',
        'Phone'     => 'phone'
    ];


    private $questionsValueKeys = [
        'Email'     => 'email',
        'Firstname' => 'first',
        'Lastname'  => 'last',
        'Phone'     => 'phone',
        'Postcode'  => 'postcode',
        'Optin'     => 'opt',
        'Gender'    => 'gender',
        'Country'   => 'country'
    ];



    public function __construct(LocationSettings $location, ?UserProfile $profile)
    {
        $this->location = $location;
        $this->profile  = $profile;
        $this->story = new NearlyStory($location->getSerial(), 10);
        $this->story->setEnabled(false);
    }

    /**
     * @var LocationSettings
     */
    private $location;

    /**
     * @var UserProfile
     */
    private $profile;

    /**
     * @var OrganizationRegistration
     */
    private $organisationRegistration;

    /**
     * @var ReviewSettings
     */
    private $reviewSettings;

    /**
     * @var LocationPlans[]
     */
    private $paymentPlans = [];

    /**
     * @var UserDevices[]
     */
    private $paymentDevices = [];
    /**
     * @var array
     */
    private $paymentTransactions =  null;

    /**
     * @var string
     */
    private $stripeCustomerId =  '';

    /**
     * @var string
     */
    private $stripePaymentMethods =  [];

    /**
     * @var bool
     */
    private $blocked =  false;

    /**
     * @var bool
     */
    private $trackAndTraceEnabled =  false;

    /**
     * @var bool
     */
    private $validSubscription =  true;

    /**
     * @var NearlyStory
     */
    private $story;

    /**
     * @var NearlyAuthenticationResponse
     */
    private $authPayload = null;

    /**
     * @var string
     */
    private $impressionId = '';

    public function getQuestions()
    {
        if ($this->location->getType() === 1) {
            return array_keys($this->paidQuestionsKeys);
        }
        return $this->location->getFreeQuestions();
    }

    /**
     * @return NearlyQuestion[]
     */
    public function getFreeQuestions(): array
    {
        $defaults = [
            'email',
            'first',
            'last',
            'phone',
            'postcode',
            'opt',
            'gender',
            'country',
            'birthMonth',
            'birthDay'
        ];

        /** @var NearlyQuestion[] $questions */
        $questions = [];
        foreach ($defaults as $default) {
            $questions[$default] = new NearlyQuestion($default, false);
        }

        foreach ($this->getQuestions() as $question) {
            if ($question === 'DoB') {
                $questions['birthDay']->setVisible(true);
                $questions['birthMonth']->setVisible(true);
                continue;
            }
            $keyValue = $this->questionsValueKeys[$question];
            $questions[$keyValue]->setVisible(true);
        }

        if (!is_null($this->profile)) {
            foreach ($this->profile->jsonSerialize() as $profileKey => $value) {
                if (array_key_exists($profileKey, $questions)) {
                    if (!is_null($value)) {
                        $questions[$profileKey]->setVisible(false);
                    }
                }
            }
        }

        if ($questions['opt']->getVisible()) {
            $questions['opt']->setText($this->location->getOtherSettings()->getOptText());
            $questions['opt']->setRequired($this->location->getOtherSettings()->getOptRequired());
            $questions['opt']->setDefaultValue($this->location->getOtherSettings()->getOptChecked());
        }

        if (
            !is_null($this->profile) &&
            !$questions['email']->getVisible() &&
            $this->location->getOtherSettings()->getValidation() &&
            !$this->profile->getVerified()
        ) {
            $questions['email']->setVisible(true);
        }

        return $questions;
    }

    public function getCustomQuestions(): array
    {
        if (is_null($this->profile)) {
            return $this->location->getCustomQuestions();
        }
        if (empty($this->profile->getCustom())) {
            return $this->location->getCustomQuestions();
        }

        $customQuestionsAtLocation = $this->profile->getCustomForSerial($this->location->getSerial());
        if (empty($customQuestionsAtLocation)) {
            return $this->location->getCustomQuestions();
        }
        $custom = [];
        foreach ($this->location->getCustomQuestions() as $question) {
            if (!isset($customQuestionsAtLocation[$question['id']])) {
                $custom[] = $question;
            }
        }
        return $custom;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function getLocation(): LocationSettings
    {
        return $this->location;
    }

    public function getOrganisationRegistration(): OrganizationRegistration
    {
        return $this->organisationRegistration;
    }


    public function setOrganisationRegistration(OrganizationRegistration $organisationRegistration)
    {
        $this->organisationRegistration = $organisationRegistration;
    }

    /**
     * @param $paymentPlans LocationPlan[]
     */
    public function setPaymentPlans(array $paymentPlans)
    {
        $this->paymentPlans = $paymentPlans;
    }

    /**
     * @param $paymentPlans UserDevices[]
     */
    public function setPaymentDevices(array $paymentDevices)
    {
        $this->paymentDevices = $paymentDevices;
    }


    public function setPaymentTransactions(array $paymentTransactions)
    {
        $this->paymentTransactions = $paymentTransactions;
    }

    public function setStripeCustomerId(string $stripeCustomerId)
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    public function setStripePaymentMethods(array $stripePaymentMethods)
    {
        $this->stripePaymentMethods = $stripePaymentMethods;
    }

    public function setBlocked(bool $blocked)
    {
        $this->blocked = $blocked;
    }

    public function getBlocked()
    {
        return $this->blocked;
    }

    public function setValidSubscription(bool $validSubscription)
    {
        $this->validSubscription = $validSubscription;
    }

    public function setImpressionId(string $impressionId)
    {
        $this->impressionId = $impressionId;
    }

    public function setStory(NearlyStory $story)
    {
        $this->story = $story;
    }

    public function getStory(): NearlyStory
    {
        if (empty($this->story->getPages())) {
            $this->story->setEnabled(false);
        }
        return $this->story;
    }


    public function shouldAutoAuth(): bool
    {
        foreach ($this->getFreeQuestions() as $question) {
            if ($question->getVisible()) {
                return false;
            }
        }
        if (
            !$this->getOrganisationRegistration()->getSmsOptIn() ||
            !$this->getOrganisationRegistration()->getEmailOptIn() ||
            !$this->getOrganisationRegistration()->getDataOptIn()
        ) {
            return false;
        }
        if (!empty($this->getCustomQuestions())) {
            return false;
        }
        if ($this->location->getType() !== 0) {
            return false;
        }
        if (!is_null($this->reviewSettings)) {
            return false;
        }
        if ($this->story->getEnabled()) {
            return false;
        }
        if (is_null($this->profile)) {
            return false;
        }
        return true;
    }

    public function setAuthPayload(NearlyAuthenticationResponse $authPayload)
    {
        $this->authPayload = $authPayload;
    }

    public function setReviewSettings(ReviewSettings $reviewSettings = null)
    {
        $this->reviewSettings = $reviewSettings;
    }

    public function getAuthPayload()
    {
        if (is_null($this->authPayload)) {
            return null;
        }
        return $this->authPayload->jsonSerialize();
    }

    public function getOutProfile()
    {
        if (is_null($this->profile)) {
            return null;
        }
        return $this->profile->jsonSerialize();
    }

    public function setTrackAndTraceEnabled(bool $trackAndTraceEnabled)
    {
        $this->trackAndTraceEnabled = $trackAndTraceEnabled;
    }

    public function jsonSerialize()
    {
        return [
            'impression_id' => $this->impressionId,
            'reviews_settings' => $this->reviewSettings,
            'location'    => $this->location->jsonSerialize(),
            'profile'    => $this->getOutProfile(),
            'branding' => $this->location->getBrandingSettings(),
            'other' => $this->location->getOtherSettings(),
            'facebook' => $this->location->getFacebookSettings(),
            'blocked' => $this->blocked,
            'story' => $this->getStory(),
            'organization_name' => $this->location->getOrganization()->getName(),
            'organization_id' => $this->location->getOrganization()->getId()->toString(),
            'valid_subscription' => $this->validSubscription,
            'track_and_trace' => $this->trackAndTraceEnabled,
            'payment' => [
                'plans' => $this->paymentPlans,
                'devices' => $this->paymentDevices,
                'transactions' => $this->paymentTransactions,
                'stripe' => [
                    'customer_id' => $this->stripeCustomerId,
                    'methods' => $this->stripePaymentMethods
                ],
            ],
            'questions'     => [
                'standard' => $this->getFreeQuestions(),
                'custom' => $this->getCustomQuestions()
            ],
            'auth_payload' => $this->getAuthPayload(),
            'should_auto_auth' => $this->shouldAutoAuth(),
            'opt_in' => [
                'marketing' => $this->getOrganisationRegistration()->getSmsOptIn() &&
                    $this->getOrganisationRegistration()->getEmailOptIn(),
                'data' => $this->getOrganisationRegistration()->getDataOptIn()
            ]
        ];
    }
}
