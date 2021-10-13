<?php


namespace App\Package\GiftCard;


use App\Models\GiftCard;
use App\Models\UserProfile;
use DateTime;
use JsonSerializable;

/**
 * Class GiftCardEvent
 * @package App\Package\GiftCard
 */
class GiftCardEvent implements JsonSerializable
{
    /**
     * @param GiftCard $giftCard
     * @return static
     */
    public static function created(GiftCard $giftCard): self
    {
        return new self(
            self::TYPE_CREATED,
            $giftCard
        );
    }

    /**
     * @param GiftCard $giftCard
     * @return static
     */
    public static function activated(GiftCard $giftCard): self
    {
        return new self(
            self::TYPE_ACTIVATED,
            $giftCard
        );
    }

    /**
     * @param GiftCard $giftCard
     * @return static
     */
    public static function redeemed(GiftCard $giftCard): self
    {
        return new self(
            self::TYPE_REDEEMED,
            $giftCard
        );
    }

    /**
     * @param GiftCard $giftCard
     * @return static
     */
    public static function refunded(GiftCard $giftCard): self
    {
        return new self(
            self::TYPE_REFUNDED,
            $giftCard
        );
    }

    public static function ownershipChanged(
        GiftCard $card,
        UserProfile $fromProfile,
        UserProfile $toProfile
    ): self {
        return new self(
            self::TYPE_OWNERSHIP_CHANGED,
            $card,
            [
                'fromProfileId' => $fromProfile->getId(),
                'fromEmail'     => $fromProfile->getEmail(),
                'toProfileId'   => $toProfile->getId(),
                'toEmail'       => $toProfile->getEmail()
            ]
        );
    }

    const TYPE_CREATED = 'created';

    const TYPE_ACTIVATED = 'activated';

    const TYPE_REDEEMED = 'redeemed';

    const TYPE_REFUNDED = 'refunded';

    const TYPE_OWNERSHIP_CHANGED = 'ownership_changed';

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var GiftCard $giftCard
     */
    private $giftCard;

    /**
     * @var array $eventSpecificMetadata
     */
    private $eventSpecificMetadata;

    /**
     * GiftCardEvent constructor.
     * @param string $type
     * @param GiftCard $giftCard
     * @param array $eventSpecificMetadata
     */
    private function __construct(
        string $type,
        GiftCard $giftCard,
        array $eventSpecificMetadata = []
    ) {
        $this->type                  = $type;
        $this->giftCard              = $giftCard;
        $this->eventSpecificMetadata = $eventSpecificMetadata;
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
        return array_merge(
            [
                'type'               => $this->type,
                'id'                 => $this->giftCard->getId()->toString(),
                'humanId'            => $this->giftCard->humanID(),
                'giftCardSettingsId' => $this->giftCard->getGiftCardSettingsId()->toString(),
                'fee'                => $this->giftCard->fee(),
                'amount'             => $this->giftCard->getAmount(),
                'currency'           => $this->giftCard->getCurrency(),
                'formattedAmount'    => $this->giftCard->formattedCurrency(),
                'createdAt'          => self::formattedDateTimeOrNull($this->giftCard->getCreatedAt()),
                'redeemedAt'         => self::formattedDateTimeOrNull($this->giftCard->getRedeemedAt()),
                'refundedAt'         => self::formattedDateTimeOrNull($this->giftCard->getRefundedAt()),
                'profileId'          => $this->giftCard->getProfileId(),
                'status'             => $this->giftCard->status(),
                'description'        => $this->giftCard->description(),
                'redeemedById'       => $this->giftCard->getRedeemedById(),
                'refundedById'       => $this->giftCard->getRefundedBy(),
                'organizationId'     => $this->giftCard->getGiftCardSettings()->getOrganizationId()->toString(),
            ],
            $this->eventSpecificMetadata
        );
    }

    /**
     * @param DateTime|null $dateTime
     * @return string|null
     */
    private static function formattedDateTimeOrNull(?DateTime $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }
        return $dateTime->format(DateTime::RFC3339_EXTENDED);
    }
}