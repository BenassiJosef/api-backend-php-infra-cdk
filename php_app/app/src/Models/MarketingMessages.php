<?php

namespace App\Models;

use App\Models\Organization;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * MarketingMessages
 *
 * @ORM\Table(name="marketing_campaign_messages")
 * @ORM\Entity
 */
class MarketingMessages implements JsonSerializable
{

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(
        array $data,
        Organization $organization,
        ?MarketingMessages $message = null
    ): self {
        if (!$message) {
            $def = new self($organization);
        } else {
            $def = $message;
        }
        $def->touch();
        $def->id = $data['id'] ?? $def->getId();
        $def->name = $data['name'] ?? $def->getName();
        $def->emailContents = $data['emailContents'] ?? $def->getEmailContents();
        $def->emailContentsJson = $data['emailContentsJson'] ?? $def->getEmailContentsJson();
        $def->smsSender = $data['smsSender'] ?? $def->getSmsSender();
        $def->smsContents = $data['smsContents'] ?? $def->getSmsContents();
        $def->sendToSms = $data['sendToEmail'] ?? $def->getSendToEmail();

        $def->sendToSms = $data['sendToSms'] ?? $def->getSendToSms();
        $def->subject = $data['subject'] ?? $def->getSubject();
        $def->templateType = $data['templateType'] ?? $def->getTemplateType();

        return $def;
    }

    public function __construct(Organization $organization)
    {
        $this->admin = $organization->getOwnerId()->toString();
        $this->organizationId = $organization->getId();
        $this->created = new DateTime();
        $this->deleted = false;
        $this->sendToEmail = true;
        $this->sendToSms = false;
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
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=128, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="smsContents", type="string", length=160, nullable=true)
     */
    private $smsContents;

    /**
     * @var string
     *
     * @ORM\Column(name="emailContents", type="text",  nullable=true)
     */
    private $emailContents;

    /**
     * @var array
     * @ORM\Column(name="emailContentsJSON", type="json", nullable=true)
     */
    private $emailContentsJson;

    /**
     * @var string
     *
     * @ORM\Column(name="smsSender", type="string", length=12, nullable=true)
     */
    private $smsSender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sendToSms", type="boolean", nullable=true)
     */
    private $sendToSms;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sendToEmail", type="boolean", nullable=true)
     */
    private $sendToEmail;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */
    private $deleted = false;

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
     * @var string
     *
     * @ORM\Column(name="templateType", type="string")
     */
    private $templateType = 'builder';

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
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getSmsContents(): ?string
    {
        return $this->smsContents;
    }

    /**
     * @return string
     */
    public function getSmsSender(): ?string
    {
        return $this->smsSender;
    }

    /**
     * @return string
     */
    public function getEmailContents(): ?string
    {
        return $this->emailContents;
    }

    /**
     * @return array
     */
    public function getEmailContentsJson(): ?array
    {
        return $this->emailContentsJson;
    }

    /**
     * @return bool
     */
    public function getSendToSms(): ?bool
    {
        return $this->sendToSms;
    }

    /**
     * @return bool
     */
    public function getSendToEmail(): ?bool
    {
        return $this->sendToEmail;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getTemplateType(): ?string
    {
        return $this->templateType;
    }

    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'created' => $this->getCreatedAt(),
            'name' => $this->getName(),
            'organizationId' => $this->getOrganizationId(),
            'emailContents' => $this->getEmailContents(),
            'emailContentsJson' => $this->getEmailContentsJson(),
            'smsSender' => $this->getSmsSender(),
            'smsContents' => $this->getSmsContents(),
            'sendToSms' => $this->getSendToSms(),
            'sendToEmail' => $this->getSendToEmail(),
            'subject' => $this->getSubject(),
            'templateType' => $this->getTemplateType(),
        ];
    }

    public function touch()
    {
        $this->edited = new DateTime();
    }

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
}
