<?php


namespace App\Models\WebTracking;

use App\Models\Organization;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use DateTime;

/**
 * Website
 *
 * @ORM\Table(name="website")
 * @ORM\Entity
 */
class Website implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var IDPrettyfier $idPrettyfier
     */
    private $idPrettyfier;

    /**
     * @ORM\Column(name="organization_id", type="uuid")
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
     * @ORM\Column(name="url", type="string")
     * @var string url
     */
    private $url;


    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * Website constructor.
     * @param Organization $organization
     * @param string $url
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string $url
    )
    {
        $this->id             = Uuid::uuid4();
        $this->url            = $url;
        $this->organizationId = $organization->getId();
        $this->organization   = $organization;
        $this->createdAt      = new DateTime();
        $this->idPrettyfier   = new HumanReadable();
    }

    /**
     * @param string $url
     * @return string
     */
    public function setUrl(string $url): string
    {
        $this->url = $url;

        return $this->url;
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }


    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
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
     * @return string
     */
    public function humanID(): string
    {
        if ($this->idPrettyfier === null) {
            $this->idPrettyfier = new HumanReadable();
        }

        return $this->idPrettyfier->prettify($this->getId());
    }


    public function jsonSerialize()
    {
        return [
            "id"        => $this->getId(),
            "prettyId"  => $this->humanID(),
            "url"       => $this->getUrl(),
            "createdAt" => $this->getCreatedAt()
        ];
    }

}

