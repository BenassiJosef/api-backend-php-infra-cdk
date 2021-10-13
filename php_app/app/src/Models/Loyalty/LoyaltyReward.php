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
 * Class LoyaltyReward
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_reward")
 * @ORM\Entity
 */
class LoyaltyReward implements JsonSerializable, Reward
{

    /**
     * @param Organization $organization
     * @param string $name
     * @param int $amount
     * @param string $currency
     * @return static
     * @throws Exception
     */
    public static function newValueReward(
        Organization $organization,
        string $name,
        int $amount,
        string $currency = 'GBP'
    ): self {
        $reward           = new self(
            $organization,
            $name,
            self::TYPE_VALUE
        );
        $reward->amount   = $amount;
        $reward->currency = $currency;
        return $reward;
    }

    /**
     * @param Organization $organization
     * @param string $name
     * @param string|null $code
     * @return static
     * @throws Exception
     */
    public static function newItemReward(
        Organization $organization,
        string $name,
        ?string $code = null
    ): self {
        $reward       = new self(
            $organization,
            $name,
            self::TYPE_ITEM
        );
        $reward->code = $code;
        return $reward;
    }

    const TYPE_ITEM = 'item';

    const TYPE_VALUE = 'value';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
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
     * @ORM\Column(name="name", type="string", nullable=false)
     * @var string $name
     */
    private $name;

    /**
     * @ORM\Column(name="code", type="string", nullable=true)
     * @var string | null $code
     */
    private $code;

    /**
     * @ORM\Column(name="amount", type="integer", nullable=true)
     * @var int | null $amount
     */
    private $amount;

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     * @var string | null $currency
     */
    private $currency;

    /**
     * @ORM\Column(name="type", type="string", nullable=false)
     * @var string $type
     */
    private $type;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @var DateTime | null $deletedAt
     */
    private $deletedAt;

    /**
     * LoyaltyReward constructor.
     * @param Organization $organization
     * @param string $name
     * @param string $type
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string $name,
        string $type
    ) {
        $this->id             = Uuid::uuid1();
        $this->organizationId = $organization->getId();
        $this->organization   = $organization;
        $this->name           = $name;
        $this->type           = $type;
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
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
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function delete()
    {
        $this->deletedAt = new DateTime();
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
        switch ($this->type) {
            case self::TYPE_VALUE:
                return [
                    'id'             => $this->getId()->toString(),
                    'organizationId' => $this->organizationId->toString(),
                    'name'           => $this->name,
                    'amount'         => $this->amount,
                    'currency'       => $this->currency,
                    'type'           => $this->type,
                    'createdAt'      => $this->createdAt,
                ];
                break;
            case self::TYPE_ITEM:
                return [
                    'id'             => $this->getId()->toString(),
                    'organizationId' => $this->organizationId->toString(),
                    'name'           => $this->name,
                    'code'           => $this->code,
                    'type'           => $this->type,
                    'createdAt'      => $this->createdAt,
                ];
                break;
        }
    }
}
