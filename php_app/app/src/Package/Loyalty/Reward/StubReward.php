<?php


namespace App\Package\Loyalty\Reward;


use DateTime;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class StubReward implements Reward
{

    public static function fromArray(array $data): self
    {
        $reward                 = new self();
        $reward->id             = Uuid::fromString($data['id']);
        $reward->organizationId = Uuid::fromString($data['organizationId']);
        $reward->name           = $data['name'];
        $reward->code           = $data['code'];
        $reward->amount         = $data['amount'];
        $reward->currency       = $data['currency'];
        $reward->type           = $data['type'];
        $reward->createdAt      = new DateTime($data['createdAt']);
        return $reward;
    }

    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string | null $code
     */
    private $code;

    /**
     * @var int | null $amount
     */
    private $amount;

    /**
     * @var string | null $currency
     */
    private $currency;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var DateTime $createdAt
     */
    private $createdAt;

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
     * @return int|null
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
}
