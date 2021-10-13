<?php


namespace App\Controllers\User;


use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Models\OrganizationAccess;
use Ramsey\Uuid\UuidInterface;
use JsonSerializable;

/**
 * Class Me
 * @package App\Controllers\User
 */
class Me implements JsonSerializable
{
    /**
     * @var UuidInterface $uid
     */
    private $id;

    /**
     * @var Organization[] $organisations
     */
    private $organisations = [];

    /**
     * @var LocationSettings[]
     */
    private $locations = [];

    /**
     * @var OrganizationAccess[]
     */
    private $organisationAccess;

    /**
     * Me constructor.
     * @param UuidInterface $id
     * @param Organization[] $organisations
     * @param LocationSettings[] $locations
     * @param OrganizationAccess[] $organisationAccess
     */
    public function __construct(UuidInterface $id, array $organisations, array $locations, array $organisationAccess)
    {
        $this->id            = $id;
        $this->organisations = $organisations;
        $this->locations     = $locations;
        $this->organisationAccess = $organisationAccess;
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return Organization[]
     */
    public function getOrganisations(): array
    {
        return $this->organisations;
    }

    /**
     * @return LocationSettings[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @return OrganizationAccess[]
     */
    public function getOrganisationAccess(): array
    {
        return $this->organisationAccess;
    }

    public function jsonSerialize()
    {
        return [
            'id'            => $this->getId(),
            'organisations' => array_values($this->getOrganisations()),
            'organisationAccess' => $this->getOrganisationAccess(),
            'locations'     => $this->getLocations(),
        ];
    }
}