<?php

namespace App\Models;

use App\Models\MarketingMessages;
use App\Models\Marketing\TemplateSettings;
use App\Package\Marketing\MarketingMessage;
use App\Package\Marketing\MarketingReportRow;
use DateTime;
use DoctrineExtensions\Query\Mysql\Date;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * MarketingCampaigns
 *
 * @ORM\Table(name="marketing_campaigns")
 * @ORM\Entity
 */
class MarketingCampaigns implements JsonSerializable
{
    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(
        array $data,
        Organization $organization,
        ?MarketingCampaigns $campaign = null
    ): self {
        if (!$campaign) {
            $def = new self($organization);
        } else {
            $def = $campaign;
            $def->message = MarketingMessages::fromArray($data['message'] ?? [], $organization, $def->getMessage());
        }
        $def->touch();
        $def->id = $data['id'] ?? $def->getId();
        $def->active = $data['active'] ?? $def->isActive();
        $def->hasLimit = $data['hasLimit'] ?? $def->getLimit();
        $def->eventId = $data['eventId'] ?? $def->getEventId();
        $def->filterId = $data['filterId'] ?? $def->getFilterId();
        $def->messageId = $data['messageId'] ?? $def->getMessage()->getId();
        $def->name = $data['name'] ?? $def->getName();
        $def->admin = $data['admin'] ?? $def->getAdmin();
        $def->limit = $data['limit'] ?? $def->getLimit();
        $def->spendPerHead = $data['spendPerHead'] ?? $def->getSpendPerHead();
        $def->deleted = $data['deleted'] ?? $def->isDeleted();
        $def->templateId = $data['templateId'] ?? $def->getTemplateId();
        $def->automation = $data['automation'] ?? $def->getAutomation();

        return $def;
    }

    public function __construct(Organization $organization)
    {
        $this->created = new DateTime();
        $this->admin = $organization->getOwnerId();
        $this->organizationId = $organization->getId();
        $this->deleted = false;
        $this->spendPerHead = 1000;
        $this->active = false;
        $this->automation = false;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hasLimit", type="boolean", nullable=true)
     */
    private $hasLimit;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="edited", type="datetime", nullable=true)
     */
    private $edited;

    /**
     * @var string
     *
     * @ORM\Column(name="eventId", type="string", length=36, nullable=true)
     */
    private $eventId;

    /**
     * @var string
     *
     * @ORM\Column(name="filter_id", type="string", length=36, nullable=true)
     */
    private $filterId;

    /**
     * @var string
     *
     * @ORM\Column(name="messageId", type="string", length=36, nullable=true)
     */
    private $messageId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\MarketingMessages", cascade={"persist"})
     * @ORM\JoinColumn(name="messageId", referencedColumnName="id", nullable=false)
     * @var MarketingMessages $message
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Marketing\TemplateSettings", cascade={"persist"})
     * @ORM\JoinColumn(name="templateId", referencedColumnName="id", nullable=false)
     * @var TemplateSettings $template
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="admin", type="string", length=36, nullable=true)
     */
    private $admin;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creditLimit", type="integer", length=11, nullable=true)
     */
    private $limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="spendPerHead", type="integer", length=11, nullable=true)
     */
    private $spendPerHead;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted;

    /**
     * @var string
     * @ORM\Column(name="templateId", type="string", nullable=true)
     */
    private $templateId;

    /**
     * @var string
     * @ORM\Column(name="automation", type="boolean", nullable=true)
     */
    private $automation;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_sent_at", type="datetime", nullable=true)
     */
    private $lastSentAt;

    /**
     * @var MarketingReportRow
     */
    private $report;

    /**
     * @return array
     */

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isHasLimit(): bool
    {
        return $this->hasLimit;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return DateTime
     */
    public function getEdited(): ?DateTime
    {
        return $this->edited;
    }

    /**
     * @return string
     */
    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    /**
     * @return string
     */
    public function getFilterId(): ?string
    {
        return $this->filterId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAdmin(): string
    {
        return $this->admin;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return int
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getSpendPerHead(): int
    {
        return $this->spendPerHead;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return string
     */
    public function getTemplateId(): string
    {
        return $this->templateId ?? '';
    }

    /**
     * @return string
     */
    public function getAutomation(): string
    {
        return $this->automation;
    }

    public function sent()
    {
        $this->lastSentAt = new DateTime();
    }

    public function getReport(): MarketingReportRow
    {
        if (is_null($this->report)) {
            return new MarketingReportRow();
        }
        return $this->report;
    }

    public function setReport(MarketingReportRow $report)
    {
        $this->report = $report;
    }

    /**
     * @return DateTime
     */
    public function getLastSentAt(): ?DateTime
    {
        return $this->lastSentAt;
    }

    /**
     * @return MarketingMessages
     */
    public function getMessage(): ?MarketingMessages
    {
        return $this->message;
    }

    /**
     * @return TemplateSettings
     */
    public function getTemplate(): ?TemplateSettings
    {
        return $this->template;
    }

    /**
     * @param MarketingMessages message
     */
    public function setMessage(MarketingMessages $message)
    {
        $this->message = $message;
    }

    public function touch()
    {
        $this->edited = new DateTime();
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'active' => $this->isActive(),
            'hasLimit' => $this->getLimit(),
            'created' => $this->getCreated(),
            'edited' => $this->getEdited(),
            'eventId' => $this->getEventId(),
            'filterId' => $this->getFilterId(),
            'messageId' => $this->getMessage()->getId(),
            'name' => $this->getName(),
            'admin' => $this->getAdmin(),
            'organizationId' => $this->getOrganizationId()->toString(),
            'limit' => $this->getLimit(),
            'spendPerHead' => $this->getSpendPerHead(),
            'deleted' => $this->isDeleted(),
            'templateId' => $this->getTemplateId(),
            'automation' => $this->getAutomation(),
            'last_sent_at' => $this->getLastSentAt(),
            'report' => $this->getReport()->getTotals(),
            'message' => $this->getMessage(),
            'template' => $this->getTemplate(),
        ];
    }
}
