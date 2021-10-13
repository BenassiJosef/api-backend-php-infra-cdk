<?php

namespace App\Package\Segments\Marketing;

use App\Models\Segments\PersistentSegment;
use App\Package\Segments\Marketing\Exceptions\InvalidCampaignTypeException;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class SendRequest
 * @package App\Package\SegmentMarketing
 */
class SendRequest implements JsonSerializable
{
    const TYPE_SMS = 'sms';

    const TYPE_EMAIL = 'email';

    /**
     * @var string[]
     */
    private static $allTypes = [
        self::TYPE_SMS,
        self::TYPE_EMAIL,
    ];

    /**
     * @param string $type
     * @throws InvalidCampaignTypeException
     */
    public static function validateCampaignType(string $type): void
    {
        if (!in_array($type, self::$allTypes)) {
            throw new InvalidCampaignTypeException($type, self::$allTypes);
        }
    }

    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var string $campaignType
     */
    private $campaignType;

    /**
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @var UuidInterface $segmentId
     */
    private $segmentId;

    /**
     * @var string $template
     */
    private $template;

    /**
     * SendRequest constructor.
     * @param string $campaignType
     * @param PersistentSegment $segment
     * @param string $template
     * @throws InvalidCampaignTypeException
     * @throws Exception
     */
    public function __construct(
        string $campaignType,
        PersistentSegment $segment,
        string $template = ''
    ) {
        self::validateCampaignType($campaignType);
        $this->id             = Uuid::uuid4();
        $this->campaignType   = $campaignType;
        $this->organizationId = $segment->getOrganizationId();
        $this->segmentId      = $segment->getId();
        $this->template       = $template;
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
    public function getCampaignType(): string
    {
        return $this->campaignType;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return UuidInterface
     */
    public function getSegmentId(): UuidInterface
    {
        return $this->segmentId;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
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
            'id'             => $this->id->toString(),
            'campaignType'   => $this->campaignType,
            'organizationId' => $this->organizationId,
            'segmentId'      => $this->segmentId,
            'template'       => $this->template,
        ];
    }
}