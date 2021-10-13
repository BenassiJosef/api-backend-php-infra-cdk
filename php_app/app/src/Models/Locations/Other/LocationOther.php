<?php

/**
 * Created by jamieaitken on 07/02/2018 at 10:12
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Other;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * LocationOther
 *
 * @ORM\Table(name="network_settings_other")
 * @ORM\Entity
 */
class LocationOther implements JsonSerializable
{

    public function __construct(bool $validation, int $hybridLimit, string $optText)
    {
        $this->validation = $validation;
        $this->hybridLimit = $hybridLimit;
        $this->optText = $optText;
        $this->smsVerification = false;
        $this->appleSignIn = true;
        $this->updatedAt = new \DateTime();
        $this->optChecked = false;
        $this->optRequired = false;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="hybridLimit", type="integer")
     */
    private $hybridLimit;

    /**
     * @var string
     * @ORM\Column(name="optText", type="string")
     */
    private $optText;

    /**
     * @var boolean
     * @ORM\Column(name="validation", type="boolean")
     */
    private $validation;

    /**
     * @var boolean
     * @ORM\Column(name="appleSignIn", type="boolean")
     */
    private $appleSignIn;

    /**
     * @var integer
     * @ORM\Column(name="validationTimeout", type="integer")
     */
    private $validationTimeout;

    /**
     * @var boolean
     * @ORM\Column(name="optChecked", type="boolean")
     */
    private $optChecked;

    /**
     * @var boolean
     * @ORM\Column(name="optRequired", type="boolean")
     */
    private $optRequired;

    /**
     * @var boolean
     * @ORM\Column(name="allowSpamEmails", type="boolean")
     */
    private $allowSpamEmails;

    /**
     * @var boolean
     * @ORM\Column(name="onlyBusinessEmails", type="boolean")
     */
    private $onlyBusinessEmails;

    /**
     * @var boolean
     * @ORM\Column(name="ddnsEnabled", type="boolean")
     */
    private $ddnsEnabled;

    /**
     * @var boolean
     * @ORM\Column(name="smsVerification", type="boolean")
     */
    private $smsVerification = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;


    public static function defaultHybridLimit()
    {
        return 1024;
    }

    public static function defaultOptText()
    {
        return 'I want to receive news and special offers';
    }

    public static function defaultValidation()
    {
        return true;
    }

    public static function defaultAllowSpamEmails()
    {
        return false;
    }

    public static function defaultOnlyBusinessEmails()
    {
        return false;
    }

    public static function defaultDDNSEnabled()
    {
        return false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDdnsEnabled(): bool
    {
        return $this->ddnsEnabled;
    }

    public function getOptRequired(): bool
    {
        return $this->optRequired;
    }

    public function getOptChecked(): bool
    {
        return $this->optChecked;
    }

    public function getHybridLimit(): int
    {
        return $this->hybridLimit;
    }

    public function getOptText(): string
    {
        return $this->optText;
    }

    public function getValidation(): bool
    {
        return $this->validation;
    }

    public function jsonSerialize()
    {
        return [
            "hybrid_limit" => $this->hybridLimit,
            "opt_text" => $this->optText,
            "validation" => $this->validation,
            "validation_timeout" => $this->validationTimeout,
            "opt_checked" => $this->optChecked,
            "opt_required" => $this->optRequired,
            "allow_spam_emails" => $this->allowSpamEmails,
            "only_business_emails" => $this->onlyBusinessEmails,
            "ddns_enabled" => $this->ddnsEnabled,
            "sms_verification" => $this->smsVerification,
            "apple_signin" => $this->appleSignIn
        ];
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}
