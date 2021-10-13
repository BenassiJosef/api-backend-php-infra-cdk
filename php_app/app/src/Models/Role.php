<?php


namespace App\Models;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;

/**
 * Class Role
 *
 * @ORM\Table(name="role")
 * @ORM\Entity
 * @package App\Models
 */
class Role implements JsonSerializable
{
    /**
     * @var int InvalidLegacyId
     */
    const InvalidLegacyId = -1;

    /**
     * @var int LegacySuperAdmin
     */
    const LegacySuperAdmin = 0;

    /**
     * @var int LegacyReseller
     */
    const LegacyReseller = 1;

    /**
     * @var int LegacyAdmin
     */
    const LegacyAdmin = 2;

    /**
     * @var int LegacyModerator
     */
    const LegacyModerator = 3;

    /**
     * @var int LegacyReports
     */
    const LegacyReports = 4;

    /**
     * @var int LegacyMarketeer
     */
    const LegacyMarketeer = 5;

    /**
     * @var array
     */
    public static $allRoles = [
        self::LegacySuperAdmin,
        self::LegacyReseller,
        self::LegacyAdmin,
        self::LegacyModerator,
        self::LegacyMarketeer,
        self::LegacyReports
    ];

    /**
     * @var array
     */
    public static $stringRepresentation = [
        self::InvalidLegacyId  => 'invalid',
        self::LegacySuperAdmin => 'super-admin',
        self::LegacyReseller   => 'reseller',
        self::LegacyAdmin      => 'admin',
        self::LegacyModerator  => 'moderator',
        self::LegacyReports    => 'reports',
        self::LegacyMarketeer  => 'marketeer'
    ];

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     * @var string $name
     */
    private $name;

    /**
     * @ORM\Column(name="legacy_id", type="integer")
     * @var int | null $legacyId
     */
    private $legacyId;

    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=true)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=true)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * Role constructor.
     * @param string $name
     * @param Organization|null $organization
     * @param int|null $legacyId
     * @throws Exception
     */
    public function __construct(string $name, ?Organization $organization, ?int $legacyId)
    {
        $this->id           = Uuid::uuid1();
        $this->name         = $name;
        $this->organization = $organization;
        $this->legacyId     = $legacyId;
        $this->createdAt    = new DateTime();

        if ($organization !== null) {
            $this->organizationId = $organization->getId();
        }
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    /**
     * @return Organization | null
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $legacyId       = $this->getLegacyId();
        $legacyData     = [
            'id'   => $legacyId,
            'name' => self::$stringRepresentation[$legacyId] ?? 'invalid'
        ];
        $representation = [
            'id'     => $this->getId(),
            'name'   => $this->getName(),
            'legacy' => $this->getLegacyId() == null ? null : $legacyData
        ];
        if ($this->getOrganization() !== null) {
            $representation['organizationId'] = $this->organizationId;
        }
        return $representation;
    }
}