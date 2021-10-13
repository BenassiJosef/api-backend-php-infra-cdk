<?php

namespace App\Models;

use App\Package\Menu\MenuItemDefinition;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Menu
 *
 * @ORM\Table(name="menu")
 * @ORM\Entity
 * @package App\Models
 */
class Menu implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\Column(name="items", type="json", nullable=false)
     * @var array $items
     */
    private $items;

    /**
     * @ORM\Column(name="version", type="integer", nullable=false)
     * @var integer $version
     */
    private $version;

    /**
     * @ORM\Column(name="pretty_id", type="string", nullable=false)
     * @var string $prettyId
     */
    private $prettyId;

    /**
     * @ORM\Column(name="icon", type="string", nullable=false)
     * @var string $icon
     */
    private $icon;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=false)
     * @var DateTime $deletedAt
     */
    private $deletedAt;

    /**
     * OrganizationSettings constructor.
     * @param Organization $organization
     * @param string $prettyId
     * @param MenuItemDefinition[]|null $definition
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string $prettyId,
        array $definition = null
    ) {
        $this->id = Uuid::uuid1();
        $this->createdAt = new DateTime();
        $this->organization = $organization;
        $this->organizationId = $organization->getId();
        $this->prettyId = $prettyId;
        $this->version = 1;
        if ($definition === null) {
            $this->items = [];
            return;
        }

        $this->setItems($definition, 1);
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
    public function getPrettyId(): string
    {
        return $this->prettyId;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return MenuItemDefinition[]
     * @throws Exception
     */
    public function getItems(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = MenuItemDefinition::fromArray($item)->jsonSerialize();
        }
        return $items;
    }

    /**
     * @param array $definition
     * @param int|null $version
     * @return $this
     * @throws Exception
     */
    public function setItems(
        array $definition,
        ?int $version = null
    ): self {
        if ($version !== null && !$version === $this->version) {
            throw new Exception('Menu version mis match');
        }

        $this->items = [];
        foreach ($definition as $item) {
            $this->items[] = MenuItemDefinition::fromArray($item)->jsonSerialize();
        }
        return $this;
    }

    /**
     * @param string $prettyId
     */
    public function setPrettyId(string $prettyId)
    {
        $this->prettyId = $prettyId;
    }

    /**
     * @param string $icon
     */
    public function setIcon(?string $icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     * @throws Exception
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId()->toString(),
            'organization_id' => $this->getOrganizationId()->toString(),
            'items' => $this->getItems(),
            'pretty_id' => $this->getPrettyId(),
            'version' => $this->getVersion(),
            'created_at' => $this->getCreatedAt(),
            'icon' => $this->icon,
        ];
    }

    public function jsonSerializeSitemap()
    {
        return [
            'items' => $this->getItems(),
            'pretty_id' => $this->getPrettyId(),
            'icon' => $this->icon,
        ];
    }
}
