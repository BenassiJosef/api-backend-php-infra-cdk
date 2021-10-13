<?php

namespace App\Models\Loyalty;

use App\Models\Organization;
use App\Package\Loyalty\Reward\Reward;
use DateTime;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LoyaltySecondary
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_secondary_id")
 * @ORM\Entity
 */
class LoyaltySecondary implements JsonSerializable
{
    public static function fromArray(LoyaltyStampScheme $scheme, array $data): self
    {
        $id = Uuid::uuid4();
        if (array_key_exists('id', $data) && $data['id'] !== null) {
            $id = Uuid::fromString($data['id']);
        }
        $secondary         = new self(
            $scheme,
            $data['type'] ?? 'qr',
            $id
        );
        $secondary->serial = $data['serial'] ?? null;
        return $secondary;
    }

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="scheme_id", type="uuid", nullable=false)
     * @var UuidInterface $schemeId
     */
    private $schemeId;

    /**
     * @ORM\ManyToOne(targetEntity="LoyaltyStampScheme", cascade={"persist"})
     * @ORM\JoinColumn(name="scheme_id", referencedColumnName="id", nullable=false)
     * @var LoyaltyStampScheme $scheme
     */
    private $scheme;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="last_used_at", type="datetime", nullable=true)
     * @var DateTime $lastUsedAt
     */
    private $lastUsedAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @var bool $deletedAt
     */
    private $deletedAt;

    /**
     * @ORM\Column(name="type", type="string", nullable=false)
     * @var string $type
     */
    private $type;

    /**
     * @ORM\Column(name="serial", type="string", nullable=true)
     * @var string | null $serial
     */
    private $serial;

    /**
     * LoyaltySecondary constructor.
     * @param LoyaltyStampScheme $scheme
     * @param string $type
     * @param UuidInterface|null $id
     * @throws Exception
     */
    public function __construct(
        LoyaltyStampScheme $scheme,
        string $type,
        ?UuidInterface $id = null
    ) {
        if (is_null($id)) {
            $this->id = Uuid::uuid4();
        } else {
            $this->id = $id;
        }

        $this->createdAt = new DateTime();
        $this->schemeId  = $scheme->getId();
        $this->scheme    = $scheme;
        $this->type      = $type;
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
    public function getSchemeId(): UuidInterface
    {
        return $this->schemeId;
    }

    /**
     * @return LoyaltyStampScheme
     */
    public function getScheme(): LoyaltyStampScheme
    {
        return $this->scheme;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    /**
     * @param bool $isActive
     */
    public function setActive(bool $isActive)
    {
        if ($isActive) {
            $this->deletedAt = null;
        } else {
            $this->deletedAt = new DateTime();
        }
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getLastUsedAt(): ?DateTime
    {
        return $this->lastUsedAt;
    }

    public function touch()
    {
        $this->lastUsedAt = new DateTime();
    }

    /**
     * @param string $serial
     */
    public function setSerial(?string $serial)
    {
        $this->serial = $serial;
    }

    /**
     * @return string|null
     */
    public function getSerial(): ?string
    {
        return $this->serial;
    }

    /**
     * @return string
     */
    public function getDeepAppUrl(): string
    {
        return 'https://l.stmpd.ai/stamp/' . $this->getType() . '?tag=' . $this->getId()->toString();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'           => $this->getId()->toString(),
            'scheme_id'    => $this->schemeId->toString(),
            'active'       => $this->isActive(),
            'type'         => $this->getType(),
            'created_at'   => $this->getCreatedAt(),
            'last_used_at' => $this->getLastUsedAt(),
            'app_url'      => $this->getDeepAppUrl(),
            'serial'       => $this->getSerial(),
        ];
    }
}
