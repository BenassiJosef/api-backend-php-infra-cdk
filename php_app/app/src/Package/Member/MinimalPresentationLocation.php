<?php


namespace App\Package\Member;

use App\Models\Locations\LocationSettings;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

class MinimalPresentationLocation implements JsonSerializable
{
    /**
     * @var LocationSettings $organization
     */
    private $location;

    /**
     * MinimalPresentationLocation constructor.
     * @param LocationSettings $organization
     */
    public function __construct(LocationSettings $location)
    {
        $this->location = $location;
    }

    public function getId(): string
    {
        return $this->location->getSerial();
    }

    public function getOrganizationId(): ?UuidInterface
    {
        return $this->location->getOrganizationId();
    }

    public function getName(): ?string
    {
        return $this->location->getAlias();
    }

    public function getIcon(): ?string
    {
        if (is_null($this->location->getBrandingSettings())) {
            return null;
        }
        return $this->location->getBrandingSettings()->getHeaderImage();
    }

    public function getHeaderColor(): ?string
    {
        if (is_null($this->location->getBrandingSettings())) {
            return null;
        }
        return $this->location->getBrandingSettings()->getHeaderColor();
    }

    public function getPrimaryColor(): ?string
    {
        if (is_null($this->location->getBrandingSettings())) {
            return null;
        }
        return $this->location->getBrandingSettings()->getInterfaceColor();
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
        return [
            'id'                   => $this->getId(),
            'organisation_id' => $this->getOrganizationId()->toString(),
            'name'                 => $this->getName(),
            'primary_color' => $this->getPrimaryColor(),
            'header_color' => $this->getHeaderColor(),
            'icon' => $this->getIcon()
        ];
    }
}
