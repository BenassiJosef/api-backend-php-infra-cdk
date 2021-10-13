<?php


namespace App\Package\Loyalty\Events;


use App\Models\Loyalty\LoyaltyStampCardEvent;
use DateTime;
use InvalidArgumentException;
use JsonSerializable;

class StampEventMetadata implements JsonSerializable
{
    const MODE_AUTO = 'auto';

    const MODE_SELF = 'self';

    const MODE_ORGANIZATION = 'organization';

    /**
     * @var LoyaltyStampCardEvent
     */
    private $stampCardEvent;

    /**
     * StampEventMetadata constructor.
     * @param LoyaltyStampCardEvent $stampCardEvent
     */
    public function __construct(LoyaltyStampCardEvent $stampCardEvent)
    {
        $type = $stampCardEvent->getType();
        if ($type !== LoyaltyStampCardEvent::TYPE_STAMP) {
            throw new InvalidArgumentException("Cannot use (${type}) as stamp event");
        }
        $this->stampCardEvent = $stampCardEvent;
    }

    private function getMode(): string
    {
        return $this->getMetadata()['mode'];
    }

    /**
     * @return bool
     */
    public function isAutoStamp(): bool
    {
        return $this->getMode() === self::MODE_AUTO;
    }

    /**
     * @return bool
     */
    public function isOrganizationStamp(): bool
    {
        return $this->getMode() === self::MODE_ORGANIZATION;
    }

    /**
     * @return bool
     */
    public function isSelfStamp(): bool
    {
        return $this->getMode() === self::MODE_SELF;
    }

    /**
     * @return string|null
     */
    public function getSerial(): ?string
    {
        $metadata = $this->getMetadata();
        return $metadata['serial'] ?? null;
    }

    /**
     * @return bool
     */
    public function isLocationSelfStamp(): bool
    {
        return $this->isSelfStamp() && $this->getSerial() !== null;
    }

    /**
     * @return int
     */
    public function getStamps(): int
    {
        return $this->getMetadata()['stamps'];
    }

    /**
     * @return array
     */
    private function getMetadata(): array
    {
        $metadataJson = json_encode($this->stampCardEvent->getMetadata());
        return json_decode($metadataJson, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @return LoyaltyStampCardEvent
     */
    public function getStampCardEvent(): LoyaltyStampCardEvent
    {
        return $this->stampCardEvent;
    }

    /**
     * @return string|null
     */
    public function autoStampDataSource(): ?string
    {
        $metadata = $this->getMetadata();
        return $metadata['auto_stamp_data_source_key'] ?? null;
    }

    /**
     * @return string|null
     */
    public function secondaryIdType(): ?string
    {
        $metadata  = $this->getMetadata();
        $secondary = $metadata['loyalty_secondary'] ?? [];
        if (array_key_exists('type', $secondary)) {
            return $secondary['type'];
        }
        return null;
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
        return [
            'stamps'              => $this->getStamps(),
            'isLocationSelfStamp' => $this->isLocationSelfStamp(),
            'isSelfStamp'         => $this->isSelfStamp(),
            'isOrganizationStamp' => $this->isOrganizationStamp(),
            'isAutoStamp'         => $this->isAutoStamp(),
            'serial'              => $this->getSerial(),
            'mode'                => $this->getMode(),
            'dataSource'          => $this->autoStampDataSource(),
            'secondaryIdType'     => $this->secondaryIdType(),
            'id'                  => $this->stampCardEvent->getId()->toString(),
            'cardId'              => $this->stampCardEvent->getCardId()->toString(),
            'type'                => $this->stampCardEvent->getType(),
            'createdBy'           => $this->stampCardEvent->getCreatedById(),
            'createdAt'           => $this->stampCardEvent->getCreatedAt()->format(DateTime::RFC3339_EXTENDED),
        ];
    }
}