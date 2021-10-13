<?php


namespace App\Models\DataSources;

use App\Models\Locations\LocationSettings;
use Doctrine\ORM\Mapping as ORM;
use App\Models\Organization;
use App\Package\Organisations\OrganizationProvider;
use Exception;
use Ramsey\Uuid\Uuid;
use DateTime;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * Class OrganizationRegistration
 *
 * @ORM\Table(name="registration_source")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class RegistrationSource implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="organization_registration_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationRegistrationId
     */
    private $organizationRegistrationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\DataSources\OrganizationRegistration", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_registration_id", referencedColumnName="id", nullable=false)
     * @var OrganizationRegistration $organizationRegistration
     */
    private $organizationRegistration;

    /**
     * @ORM\Column(name="data_source_id", type="uuid", nullable=false)
     * @var UuidInterface $dataSourceId
     */
    private $dataSourceId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\DataSources\DataSource", cascade={"persist"})
     * @ORM\JoinColumn(name="data_source_id", referencedColumnName="id", nullable=false)
     * @var DataSource $dataSource
     */
    private $dataSource;

    /**
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     * @var string | null $serial
     */
    private $serial;

    /**
     * @ORM\Column(name="interactions", type="integer", nullable=false)
     * @var int $interactions
     */
    private $interactions;

    /**
     * @ORM\Column(name="last_interacted_at", type="datetime", nullable=false)
     * @var DateTime $lastInteractedAt
     */
    private $lastInteractedAt;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * RegistrationSource constructor.
     * @param OrganizationRegistration $organizationRegistration
     * @param DataSource $dataSource
     * @param string $serial
     * @throws Exception
     */
    public function __construct(
        OrganizationRegistration $organizationRegistration,
        DataSource $dataSource,
        string $serial
    ) {
        $this->id                         = Uuid::uuid1();
        $this->organizationRegistrationId = $organizationRegistration->getId();
        $this->organizationRegistration   = $organizationRegistration;
        $this->dataSourceId               = $dataSource->getId();
        $this->dataSource                 = $dataSource;
        $this->serial                     = $serial;
        $this->interactions               = 0;
        $this->lastInteractedAt           = new DateTime();
        $this->createdAt                  = new DateTime();
    }


    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationRegistrationId(): UuidInterface
    {
        return $this->organizationRegistrationId;
    }

    /**
     * @param OrganizationRegistration $organizationRegistration
     * @return RegistrationSource
     */
    public function setOrganizationRegistration(OrganizationRegistration $organizationRegistration): RegistrationSource
    {
        $this->organizationRegistration   = $organizationRegistration;
        $this->organizationRegistrationId = $organizationRegistration->getId();
        return $this;
    }

    /**
     * @return OrganizationRegistration
     */
    public function getOrganizationRegistration(): OrganizationRegistration
    {
        return $this->organizationRegistration;
    }

    /**
     * @return UuidInterface
     */
    public function getDataSourceId(): UuidInterface
    {
        return $this->dataSourceId;
    }

    /**
     * @return DataSource
     */
    public function getDataSource(): DataSource
    {
        return $this->dataSource;
    }

    /**
     * @return string
     */
    public function getSerial(): ?string
    {
        return $this->serial;
    }

    public function addInteractions(int $interactions = 1): self
    {
        $this->interactions += $interactions;
        return $this;
    }

    /**
     * @return int
     */
    public function getInteractions(): int
    {
        return $this->interactions;
    }

    /**
     * @return DateTime
     */
    public function getLastInteractedAt(): DateTime
    {
        return $this->lastInteractedAt;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'                 => $this->getId()->toString(),
            'interactions'       => $this->getInteractions(),
            'created_at'         => $this->getCreatedAt(),
            'last_interacted_at' => $this->getLastInteractedAt(),
            'serial'             => $this->getSerial(),
            'data_source'        => $this->getDataSource()
        ];
    }
}
