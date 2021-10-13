<?php


namespace App\Models;

use App\Package\PrettyIds\IDPrettyfier;
use App\Package\PrettyIds\URL;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Money\Currencies;
use Money\Currency;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JsonSerializable;

/**
 * Class GiftCardSettings
 *
 * @ORM\Table(name="gift_card_settings")
 * @ORM\Entity
 * @package App\Models
 */
class GiftCardSettings implements JsonSerializable
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
     * @ORM\Column(name="serial", type="string")
     * @var string $serial
     */
    private $serial;

    /**
     * @ORM\Column(name="stripe_connect_id", type="string")
     * @var int $stripeConnectId
     */
    private $stripeConnectId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\StripeConnect", cascade={"persist"})
     * @ORM\JoinColumn(name="stripe_connect_id", referencedColumnName="id", nullable=false)
     * @var StripeConnect $organization
     */
    private $stripeConnect;

    /**
     * @ORM\Column(name="title", type="string")
     * @var string $title
     */
    private $title;

    /**
     * @ORM\Column(name="description", type="string")
     * @var string $description
     */
    private $description;

    /**
     * @ORM\Column(name="image", type="string")
     * @var string $image
     */
    private $image;

    /**
     * @ORM\Column(name="background_image", type="string")
     * @var string $backgroundImage
     */
    private $backgroundImage = "";

    /**
     * @ORM\Column(name="colour", type="string")
     * @var string $colour
     */
    private $colour = "#FFCB08";

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     * @var string $currency
     */
    private $currency;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="modified_at", type="datetime", nullable=false)
     * @var DateTime $modifiedAt
     */
    private $modifiedAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=false)
     * @var DateTime | null
     */
    private $deletedAt;

    /**
     * @var IDPrettyfier $idPrettyfier
     */
    private $idPrettyfier;

    /**
     * GiftCardSettings constructor.
     * @param StripeConnect $stripeConnect
     * @param string $title
     * @param string $description
     * @param string $image
     * @param string $currency
     * @param string|null $serial
     * @throws Exception
     */
    public function __construct(
        StripeConnect $stripeConnect,
        string $title,
        string $description = "",
        string $image = "",
        string $currency = "GBP",
        string $serial = null
    ) {
        $now                   = new DateTime();
        $this->id              = Uuid::uuid1();
        $this->organization    = $stripeConnect->getOrganization();
        $this->organizationId  = $stripeConnect->getOrganization()->getId();
        $this->stripeConnect   = $stripeConnect;
        $this->stripeConnectId = $stripeConnect->getId();
        $this->title           = $title;
        $this->description     = $description;
        $this->image           = $image;
        $this->currency        = $currency;
        $this->serial          = $serial;
        $this->createdAt       = $now;
        $this->modifiedAt      = $now;

        $this->idPrettyfier = new URL();
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
    public function getSerial(): ?string
    {
        return $this->serial;
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
     * @return int
     */
    public function getStripeConnectId(): int
    {
        return $this->stripeConnectId;
    }

    /**
     * @return StripeConnect
     */
    public function getStripeConnect(): StripeConnect
    {
        return $this->stripeConnect;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getBackgroundImage(): string
    {
        return $this->backgroundImage;
    }

    /**
     * @return string
     */
    public function getColour(): string
    {
        return $this->colour;
    }

    /**
     * @return string
     */
    public function getCurrencyString(): string
    {
        return $this->currency;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return new Currency($this->currency);
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
    public function getModifiedAt(): DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @return string
     */
    public function getPrettyID(): string
    {
        if ($this->idPrettyfier === null) {
            $this->idPrettyfier = new URL();
        }
        return $this
            ->idPrettyfier
            ->prettify($this->getId());
    }

    /**
     * @param string $title
     * @return GiftCardSettings
     */
    public function setTitle(string $title): GiftCardSettings
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $description
     * @return GiftCardSettings
     */
    public function setDescription(string $description): GiftCardSettings
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $image
     * @return GiftCardSettings
     */
    public function setImage(string $image): GiftCardSettings
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @param string $backgroundImage
     * @return GiftCardSettings
     */
    public function setBackgroundImage(string $backgroundImage): GiftCardSettings
    {
        $this->backgroundImage = $backgroundImage;
        return $this;
    }

    /**
     * @param string $colour
     * @return GiftCardSettings
     */
    public function setColour(string $colour): GiftCardSettings
    {
        $this->colour = $colour;
        return $this;
    }

    /**
     * @param string $serial
     * @return GiftCardSettings
     */
    public function setSerial(?string $serial): GiftCardSettings
    {
        $this->serial = $serial;
        return $this;
    }


    /**
     * @param string $stripeConnectId
     * @return GiftCardSettings
     */
    public function setStripeConnectId(string $stripeConnectId): GiftCardSettings
    {
        $this->stripeConnectId = $stripeConnectId;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        $this->deletedAt = new DateTime();
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            "id"                => $this->getId(),
            "prettyId"          => $this->getPrettyID(),
            "organizationId"    => $this->getOrganizationId(),
            "serial"            => $this->getSerial(),
            "stripeConnectId"   => $this->getStripeConnectId(),
            "backgroundImage"   => $this->getBackgroundImage(),
            "colour"            => $this->getColour(),
            "title"             => $this->getTitle(),
            "description"       => $this->getDescription(),
            "image"             => $this->getImage(),
            "currency"          => $this->getCurrency(),
            "createdAt"         => $this->getCreatedAt(),
            "modifiedAt"        => $this->getModifiedAt(),
            "organisation_name" => $this->organization->getName()
        ];
    }
}