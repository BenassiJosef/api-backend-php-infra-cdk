<?php

namespace App\Package\Menu;

use Ramsey\Uuid\Uuid;

/**
 * Class MenuItemDefinition
 * @package App\Package\Menu
 */
class MenuItemDefinition implements \JsonSerializable
{
    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $def = new self();
        $def->id = $data['id'] ?? $def->getId();
        $def->name = $data['name'] ?? $def->getName();
        $def->link = $data['link'] ?? $def->getLink();
        $def->prefix = $data['prefix'] ?? $def->getPrefix();
        $def->description = $data['description'] ?? $def->getDescription();
        $items = $data['items'] ?? $def->getItems();

        foreach ($items as $item) {
            $def->items[] = self::fromArray($item)->jsonSerialize();
        }

        return $def;
    }

    /**
     * @var string $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var string $link
     */
    private $link;

    /**
     * @var string $prefix
     */
    private $prefix;

    /**
     * @var MenuItemDefinition[] $items
     */
    private $items = [];

    /**
     * MenuItemDefinition constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid1();
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return MenuItemDefinition[]
     */
    public function getItems(): array
    {
        return $this->items;
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
            'id' => $this->getId(),
            'name' => $this->getName(),
            'link' => $this->getLink(),
            'prefix' => $this->getPrefix(),
            'items' => $this->getItems(),
            'description' => $this->getDescription()
        ];
    }
}
