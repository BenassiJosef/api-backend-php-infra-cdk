<?php


namespace App\Models\WebTracking;

use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * WebsiteEvent
 *
 * @ORM\Table(name="website_event")
 * @ORM\Entity
 */
class WebsiteEvent implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;


    /**
     * @ORM\Column(name="website_id", type="uuid")
     * @var UuidInterface $websiteId
     */
    private $websiteId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\WebTracking\Website", cascade={"persist"})
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     * @var Website $website
     */
    private $website;

    /**
     * @ORM\Column(name="cookie", type="string")
     * @var string $cookieId
     */
    private $cookieId;


    /**
     * @ORM\Column(name="event_type", type="string")
     * @var string $eventType
     */
    private $eventType;


    /**
     * @ORM\Column(name="page_path", type="string")
     * @var string $pagePath
     */
    private $pagePath;

    /**
     * @ORM\Column(name="referral_path", type="string")
     * @var string $referralPath
     */
    private $referralPath;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * WebsiteEvent constructor.
     * @param Website $website
     * @param string $cookieId
     * @param string $eventType
     * @param string $pagePath
     * @param string $referralPath
     * @throws Exception
     */
    public function __construct(
        Website $website,
        string $cookieId,
        string $eventType,
        string $pagePath,
        string $referralPath
    )
    {
        $this->id           = Uuid::uuid4();
        $this->websiteId    = $website->getId();
        $this->website      = $website;
        $this->cookieId     = $cookieId;
        $this->eventType    = $eventType;
        $this->createdAt    = new DateTime();
        $this->pagePath     = $pagePath;
        $this->referralPath = $referralPath;
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
    public function getEventType(): string
    {
        return $this->eventType;
    }


    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getCookieId(): string
    {
        return $this->cookieId;
    }

    /**
     * @return string
     */
    public function getWebsiteId(): string
    {
        return $this->websiteId;
    }

    /**
     * @return Website
     */
    public function getWebsite(): Website
    {
        return $this->website;
    }


    /**
     * @return string
     */
    public function getPagePath(): string
    {
        return $this->pagePath;
    }

    /**
     * @return string
     */
    public function getPageReferral(): string
    {
        return $this->referralPath;
    }


    public function jsonSerialize()
    {
        return [
            "id"            => $this->getId(),
            "website"       => $this->getWebsite(),
            "website_id"    => $this->getWebsite()->getId(),
            "cookie"        => $this->getCookieId(),
            "event_type"    => $this->getEventType(),
            "page_path"     => $this->getPagePath(),
            "referral_path" => $this->getPageReferral(),
            "createdAt"     => $this->getCreatedAt()
        ];
    }

}

