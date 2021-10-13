<?php


namespace App\Package\Loyalty\Stamps;


use App\Models\DataSources\DataSource;
use App\Models\Locations\LocationSettings;
use App\Models\Loyalty\LoyaltySecondary;
use App\Models\OauthUser;
use App\Package\Loyalty\Events\StampEventMetadata;
use JsonSerializable;

class StampContext implements JsonSerializable
{

    /**
     * @return static
     */
    public static function emptyContext(): self
    {
        return new self(StampEventMetadata::MODE_ORGANIZATION);
    }

    /**
     * @param OauthUser $oauthUser
     * @return static
     */
    public static function organizationStamp(OauthUser $oauthUser): self
    {
        $ctx          = new self(StampEventMetadata::MODE_ORGANIZATION);
        $ctx->stamper = $oauthUser;
        return $ctx;
    }

    /**
     * @param LocationSettings $locationSettings
     * @return static
     */
    private static function fromLocation(LocationSettings $locationSettings): self
    {
        $ctx                   = new self(StampEventMetadata::MODE_SELF);
        $ctx->locationSettings = $locationSettings;
        return $ctx;
    }

    /**
     * @param LoyaltySecondary $secondary
     * @param LocationSettings $locationSettings
     * @return static
     */
    public static function selfStampWithLocation(
        LoyaltySecondary $secondary,
        LocationSettings $locationSettings
    ): self {
        $ctx                   = self::fromLocation($locationSettings);
        $ctx->loyaltySecondary = $secondary;
        return $ctx;
    }

    /**
     * @param LoyaltySecondary $secondary
     * @return static
     */
    public static function selfStamp(
        LoyaltySecondary $secondary
    ): self {
        $ctx                   = new self(StampEventMetadata::MODE_SELF);
        $ctx->loyaltySecondary = $secondary;
        return $ctx;
    }

    /**
     * @param DataSource $dataSource
     * @param LocationSettings $locationSettings
     * @return static
     */
    public static function autoStamp(
        DataSource $dataSource,
        LocationSettings $locationSettings
    ): self {
        $ctx                      = new self(StampEventMetadata::MODE_AUTO);
        $ctx->autoStampDataSource = $dataSource;
        $ctx->locationSettings    = $locationSettings;
        return $ctx;
    }

    /**
     * @var string $mode
     */
    private $mode;

    /**
     * @var OauthUser | null $stamper
     */
    private $stamper;

    /**
     * @var LocationSettings | null
     */
    private $locationSettings;

    /**
     * @var LoyaltySecondary | null $loyaltySecondary
     */
    private $loyaltySecondary;

    /**
     * @var DataSource | null $autoStampDataSource
     */
    private $autoStampDataSource;

    private function __construct(string $mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return OauthUser|null
     */
    public function getStamper(): ?OauthUser
    {
        return $this->stamper;
    }

    /**
     * @return LocationSettings|null
     */
    public function getLocationSettings(): ?LocationSettings
    {
        return $this->locationSettings;
    }

    /**
     * @return LoyaltySecondary|null
     */
    public function getLoyaltySecondary(): ?LoyaltySecondary
    {
        return $this->loyaltySecondary;
    }

    /**
     * @return DataSource|null
     */
    public function getAutoStampDataSource(): ?DataSource
    {
        return $this->autoStampDataSource;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        $body = [
            'mode' => $this->mode,
        ];
        if ($this->stamper !== null) {
            $body['stamper'] = $this->stamper;
        }
        if ($this->locationSettings !== null) {
            $body['serial'] = $this->locationSettings->getSerial();
        }
        if ($this->loyaltySecondary !== null) {
            $body['loyalty_secondary'] = $this->loyaltySecondary;
        }
        if ($this->autoStampDataSource !== null) {
            $body['auto_stamp_data_source']     = $this->autoStampDataSource;
            $body['auto_stamp_data_source_key'] = $this->autoStampDataSource->getKey();
        }
        return $body;
    }
}