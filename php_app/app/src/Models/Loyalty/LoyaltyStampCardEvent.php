<?php


namespace App\Models\Loyalty;

use App\Models\OauthUser;
use App\Models\UserProfile;
use App\Package\Loyalty\Stamps\StampContext;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class LoyaltyReward
 * @package App\Models\Loyalty
 * @ORM\Table(name="loyalty_stamp_card_event")
 * @ORM\Entity
 */
class LoyaltyStampCardEvent implements JsonSerializable
{
    /**
     * @param LoyaltyStampCard $card
     * @param OauthUser|null $creator
     * @return self
     * @throws Exception
     */
    public static function newCreateEvent(
        LoyaltyStampCard $card,
        ?OauthUser $creator = null
    ): self {
        return new self(
            $card,
            self::TYPE_CREATE,
            $creator
        );
    }

    /**
     * @param LoyaltyStampCard $card
     * @param OauthUser|null $activator
     * @return self
     * @throws Exception
     */
    public static function newActivateEvent(
        LoyaltyStampCard $card,
        ?OauthUser $activator = null
    ): self {
        return new self(
            $card,
            self::TYPE_ACTIVATE,
            $activator
        );
    }

    /**
     * @param LoyaltyStampCard $card
     * @param int $stamps
     * @param StampContext|null $context
     * @return self
     * @throws Exception
     */
    public static function newStampEvent(
        LoyaltyStampCard $card,
        int $stamps = 1,
        ?StampContext $context = null
    ): self {
        $stamper = null;
        if ($context !== null) {
            $stamper = $context->getStamper();
        }
        $ctxBody = [];
        if ($context !== null) {
            $ctxBody = $context->jsonSerialize();
        }
        return new self(
            $card,
            self::TYPE_STAMP,
            $stamper,
            array_merge(
                [
                    'stamps' => $stamps,
                ],
                $ctxBody
            )
        );
    }

    /**
     * @param LoyaltyStampCard $card
     * @param OauthUser|null $deleter
     * @return static
     * @throws Exception
     */
    public static function newDeleteEvent(
        LoyaltyStampCard $card,
        ?OauthUser $deleter = null
    ): self {
        return new self(
            $card,
            self::TYPE_DELETE,
            $deleter
        );
    }

    /**
     * @param LoyaltyStampCard $card
     * @return static
     * @throws Exception
     */
    public static function newFilledEvent(
        LoyaltyStampCard $card
    ): self {
        return new self(
            $card,
            self::TYPE_FILLED
        );
    }

    /**
     * @param LoyaltyStampCard $card
     * @param OauthUser|null $redeemer
     * @return self
     * @throws Exception
     */
    public static function newRedemptionEvent(
        LoyaltyStampCard $card,
        ?OauthUser $redeemer = null
    ): self {
        return new self(
            $card,
            self::TYPE_REDEEM,
            $redeemer
        );
    }

    public static $allTypes = [
        self::TYPE_CREATE,
        self::TYPE_ACTIVATE,
        self::TYPE_STAMP,
        self::TYPE_REDEEM,
        self::TYPE_DELETE,
        self::TYPE_FILLED
    ];

    const TYPE_CREATE   = 'create';
    const TYPE_ACTIVATE = 'activate';
    const TYPE_STAMP    = 'stamp';
    const TYPE_REDEEM   = 'redeem';
    const TYPE_DELETE   = 'delete';
    const TYPE_FILLED   = 'filled';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="card_id", type="uuid", nullable=false)
     * @var UuidInterface $cardId
     */
    private $cardId;

    /**
     * @ORM\ManyToOne(targetEntity="LoyaltyStampCard", cascade={"persist"})
     * @ORM\JoinColumn(name="card_id", referencedColumnName="id", nullable=false)
     * @var LoyaltyStampCard $card
     */
    private $card;

    /**
     * @ORM\Column(name="type", type="string", nullable=false)
     * @var string $type
     */
    private $type;

    /**
     * @ORM\Column(name="metadata", type="json", nullable=false)
     * @var array $metadata
     */
    private $metadata;

    /**
     * @ORM\Column(name="created_by", type="string", nullable=true)
     * @var string $createdById
     */
    private $createdById;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="uid", nullable=true)
     * @var OauthUser | null $createdBy
     */
    private $createdBy;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * LoyaltyStampCardEvent constructor.
     * @param LoyaltyStampCard $card
     * @param string $type
     * @param OauthUser $createdBy
     * @param array $metadata
     * @throws Exception
     */
    public function __construct(
        LoyaltyStampCard $card,
        string $type = self::TYPE_CREATE,
        OauthUser $createdBy = null,
        array $metadata = []
    ) {
        $this->id       = Uuid::uuid1();
        $this->card     = $card;
        $this->cardId   = $card->getId();
        $this->type     = $type;
        $this->metadata = $metadata;
        if ($createdBy !== null) {
            $this->createdById = $createdBy->getUid();
            $this->createdBy   = $createdBy;
        }
        $this->createdAt = new DateTime();
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
    public function getCardId(): UuidInterface
    {
        return $this->cardId;
    }

    /**
     * @return LoyaltyStampCard
     */
    public function getCard(): LoyaltyStampCard
    {
        return $this->card;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return string
     */
    public function getCreatedById(): ?string
    {
        return $this->createdById;
    }

    /**
     * @return OauthUser | null
     */
    public function getCreatedBy(): ?OauthUser
    {
        return $this->createdBy;
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
            'id'        => $this->id->toString(),
            'cardId'    => $this->cardId->toString(),
            'type'      => $this->type,
            'metadata'  => $this->metadata,
            'createdBy' => $this->createdById,
            'createdAt' => $this->createdAt,
        ];
    }
}
