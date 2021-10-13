<?php


namespace App\Models\Segments;

use App\Models\Organization;
use App\Package\Segments\Exceptions\InvalidReachInputException;
use App\Package\Segments\Exceptions\InvalidSegmentInputException;
use App\Package\Segments\Exceptions\UnknownNodeException;
use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonModeException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidComparisonSignatureException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidModifierException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorException;
use App\Package\Segments\Operators\Comparisons\Exceptions\InvalidOperatorForTypeException;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicalOperatorException;
use App\Package\Segments\Operators\Logic\Exceptions\InvalidLogicInputSignatureException;
use App\Package\Segments\PersistentSegmentInput;
use App\Package\Segments\Reach;
use App\Package\Segments\Segment;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidBooleanException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidIntegerException;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use App\Package\Segments\Values\DateTime\InvalidDateTimeException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidMonthException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidWeekStartDayException;
use App\Package\Segments\Values\YearDate\Exceptions\InvalidYearDateFormatException;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class SegmentEntity
 * @ORM\Table(name="segment")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @package App\Models\Segments
 */
class PersistentSegment implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

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
     * @ORM\Column(name="name", type="string")
     * @var string $name
     */
    private $name;

    /**
     * @ORM\Column(name="segment", type="json", nullable=false)
     * @var array $segment
     */
    private $segment;

    /**
     * @ORM\Column(name="version", type="uuid")
     * @var UuidInterface $version
     */
    private $version;

    /**
     * @ORM\Column(name="reach", type="json", nullable=false)
     * @var array $reach
     */
    private $reach;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @var DateTime $updatedAt
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @var DateTime | null $deletedAt
     */
    private $deletedAt;

    /**
     * SegmentEntity constructor.
     * @param Organization $organization
     * @param string $name
     * @param Segment $segment
     * @param Reach $reach
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string $name,
        Segment $segment,
        Reach $reach
    ) {
        $this->id             = Uuid::uuid1();
        $this->organization   = $organization;
        $this->organizationId = $organization->getId();
        $this->name           = $name;
        $this->segment        = $segment->jsonSerialize();
        $this->version        = Uuid::uuid4();
        $this->reach          = $reach->jsonSerialize();
        $this->createdAt      = new DateTime();
        $this->updatedAt      = new DateTime();
        $this->deletedAt      = null;
    }

    /**
     * @param string $name
     * @return PersistentSegment
     */
    public function setName(string $name): PersistentSegment
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param Segment $segment
     * @return PersistentSegment
     */
    public function setSegment(Segment $segment): PersistentSegment
    {
        $this->segment = $segment->jsonSerialize();
        return $this;
    }

    /**
     * @param Reach $reach
     * @return PersistentSegment
     */
    public function setReach(Reach $reach): PersistentSegment
    {
        $this->reach = $reach->jsonSerialize();
        return $this;
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
     * @return Segment
     * @throws InvalidSegmentInputException
     * @throws UnknownNodeException
     * @throws FieldNotFoundException
     * @throws InvalidClassException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidTypeException
     * @throws InvalidComparisonModeException
     * @throws InvalidComparisonSignatureException
     * @throws InvalidModifierException
     * @throws InvalidOperatorException
     * @throws InvalidOperatorForTypeException
     * @throws InvalidLogicInputSignatureException
     * @throws InvalidLogicalOperatorException
     * @throws InvalidBooleanException
     * @throws InvalidIntegerException
     * @throws InvalidStringException
     * @throws InvalidDateTimeException
     * @throws InvalidDayException
     * @throws InvalidMonthException
     * @throws InvalidWeekStartDayException
     * @throws InvalidYearDateFormatException
     */
    public function getSegment(): Segment
    {
        return Segment::fromArray(json_decode(json_encode($this->segment), JSON_OBJECT_AS_ARRAY));
    }

    /**
     * @return UuidInterface
     */
    public function getVersion(): UuidInterface
    {
        return $this->version;
    }

    /**
     * @return Reach
     * @throws InvalidReachInputException
     */
    public function getReach(): Reach
    {
        return Reach::fromArray(json_decode(json_encode($this->reach), JSON_OBJECT_AS_ARRAY));
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
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     * @throws Exception
     */
    public function touch(): void
    {
        $this->updatedAt = new DateTime();
        $this->version   = Uuid::uuid4();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @throws FieldNotFoundException
     * @throws InvalidBooleanException
     * @throws InvalidClassException
     * @throws InvalidComparisonModeException
     * @throws InvalidComparisonSignatureException
     * @throws InvalidDateTimeException
     * @throws InvalidDayException
     * @throws InvalidIntegerException
     * @throws InvalidLogicInputSignatureException
     * @throws InvalidLogicalOperatorException
     * @throws InvalidModifierException
     * @throws InvalidMonthException
     * @throws InvalidOperatorException
     * @throws InvalidOperatorForTypeException
     * @throws InvalidPropertiesException
     * @throws InvalidPropertyAliasException
     * @throws InvalidReachInputException
     * @throws InvalidSegmentInputException
     * @throws InvalidStringException
     * @throws InvalidTypeException
     * @throws InvalidWeekStartDayException
     * @throws InvalidYearDateFormatException
     * @throws UnknownNodeException
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'id'        => $this->getId()->toString(),
            'name'      => $this->getName(),
            'segment'   => $this->getSegment(),
            'version'   => $this->getVersion()->toString(),
            'reach'     => $this->getReach(),
            'createdAt' => $this->getCreatedAt(),
            'updatedAt' => $this->getUpdatedAt()
        ];
    }
}