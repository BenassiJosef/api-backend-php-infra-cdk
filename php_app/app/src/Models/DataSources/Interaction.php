<?php


namespace App\Models\DataSources;

use App\Models\Locations\LocationSettings;
use App\Models\UserProfile;
use DateInterval;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use App\Models\Organization;
use DateTime;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Interaction
 *
 * @ORM\Table(name="interaction")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class Interaction implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

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
     * @ORM\OneToMany(targetEntity="InteractionProfile", mappedBy="interaction", cascade={"persist"})
     * @var InteractionProfile[] | Collection | Selectable | ArrayCollection $profiles
     */
    private $profiles;

    /**
     * @ORM\OneToMany(targetEntity="InteractionSerial", mappedBy="interaction", cascade={"persist"})
     * @var InteractionSerial[] | Collection | Selectable | ArrayCollection $serials
     */
    private $serials;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="ended_at", type="datetime", nullable=true)
     * @var DateTime | null $endedAt
     */
    private $endedAt;

    /**
     * Interaction constructor.
     * @param Organization $organization
     * @param DataSource $dataSource
     * @param InteractionProfile[] $profiles
     * @param InteractionSerial[] $serials
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        DataSource $dataSource,
        array $profiles = [],
        array $serials = []
    ) {
        $this->id             = Uuid::uuid1();
        $this->organizationId = $organization->getId();
        $this->organization   = $organization;
        $this->dataSource     = $dataSource->getId();
        $this->dataSource     = $dataSource;
        $this->profiles       = new ArrayCollection($profiles);
        $this->serials        = new ArrayCollection($serials);
        $this->createdAt      = new DateTime();
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
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
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
     * @param UserProfile $userProfile
     * @return $this
     * @throws Exception
     */
    public function addProfile(UserProfile $userProfile): self
    {
        $this->profiles->add(new InteractionProfile($this, $userProfile));
        return $this;
    }

    /**
     * @return InteractionProfile[]|ArrayCollection|Collection|Selectable
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @param LocationSettings $locationSettings
     * @return $this
     * @throws Exception
     */
    public function addLocation(LocationSettings $locationSettings): self
    {
        $this->serials->add(new InteractionSerial($this, $locationSettings));
        return $this;
    }

    /**
     * @return InteractionSerial[]|ArrayCollection|Collection|Selectable
     */
    public function getSerials()
    {
        return $this->serials;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function end()
    {
        $this->endedAt = new DateTime();
    }


    public function length(): ?DateInterval
    {
        if ($this->endedAt === null) {
            return null;
        }
        return $this->endedAt->diff($this->createdAt);
    }

    public function lengthSeconds(): ?int
    {
        if ($this->endedAt === null) {
            return null;
        }
        return $this->endedAt->getTimestamp() - $this->createdAt->getTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'             => $this->getId(),
            'organizationId' => $this->getOrganizationId()->toString(),
            'dataSource'     => $this->getDataSource(),
            'serials'        => $this->getSerials()->toArray(),
            'createdAt'      => $this->getCreatedAt(),
            'endedAt'        => $this->getEndedAt(),
        ];
    }
}
