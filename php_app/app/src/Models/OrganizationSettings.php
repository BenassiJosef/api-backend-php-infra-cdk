<?php


namespace App\Models;


use App\Package\Organisations\OrganizationSettingsDefinition;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;


/**
 * Class OrganizationSettings
 *
 * @ORM\Table(name="organization_settings")
 * @ORM\Entity
 * @package App\Models
 */
class OrganizationSettings implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\Column(name="settings", type="json", nullable=false)
     * @var array $settings
     */
    private $settings;

    /**
     * @ORM\Column(name="version", type="uuid", nullable=false)
     * @var UuidInterface $version
     */
    private $version;

    /**
     * OrganizationSettings constructor.
     * @param Organization $organization
     * @param OrganizationSettingsDefinition|null $definition
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        OrganizationSettingsDefinition $definition = null
    ) {
        $this->id             = Uuid::uuid1();
        $this->organization   = $organization;
        $this->organizationId = $organization->getId();
        if ($definition === null) {
            $this->settings = (new OrganizationSettingsDefinition())->jsonSerialize();
            $this->version  = Uuid::fromString(Uuid::NIL);
            return;
        }
        $this->settings = $definition->jsonSerialize();
        $this->version  = Uuid::uuid1();
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return OrganizationSettingsDefinition
     * @throws Exception
     */
    public function getSettings(): OrganizationSettingsDefinition
    {
        return OrganizationSettingsDefinition::fromArray($this->settings);
    }

    /**
     * @param OrganizationSettingsDefinition $definition
     * @param UuidInterface|null $version
     * @return $this
     * @throws Exception
     */
    public function setSettings(
        OrganizationSettingsDefinition $definition,
        ?UuidInterface $version = null
    ): self {
        if ($version !== null && !$version->equals($this->version)) {
            throw new Exception('OrgSettings version mis match');
        }
        $this->version  = Uuid::uuid1();
        $this->settings = $definition->jsonSerialize();
        return $this;
    }

    /**
     * @return UuidInterface
     */
    public function getVersion(): UuidInterface
    {
        return $this->version;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     * @throws Exception
     */
    public function jsonSerialize()
    {
        return [
            'organizationId' => $this->getOrganizationId()->toString(),
            'settings'       => $this->getSettings(),
            'version'        => $this->getVersion()->toString(),
        ];
    }
}