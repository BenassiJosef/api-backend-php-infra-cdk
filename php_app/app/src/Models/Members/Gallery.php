<?php

/**
 * Created by jamieaitken on 20/11/2017 at 10:45
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Members;

use App\Models\Organization;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * Gallery
 *
 * @ORM\Table(name="oauth_users_gallery")
 * @ORM\Entity
 */
class Gallery implements JsonSerializable
{

    public function __construct(Organization $organization, string $url, string $kind, string $path)
    {
        $this->organizationId = $organization->getId();
        $this->user           = $organization->getOwnerId()->toString();
        $this->path           = $path;
        $this->url            = $url;
        $this->kind           = $kind;
        $this->deleted        = false;
        $this->createdAt = new DateTime();
    }

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="user", type="string")
     */
    private $user;

    /**
     * @ORM\Column(name="organization_id", type="uuid")
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @var string
     * @ORM\Column(name="path", type="string")
     */
    private $path;

    /**
     * @var string
     * @ORM\Column(name="url", type="string")
     */
    private $url;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var string
     * @ORM\Column(name="kind", type="string")
     */
    private $kind;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

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
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
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
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function jsonSerialize()
    {
        return [
            'id'    => $this->getId(),
            'user' => $this->user,
            'url'     => $this->url,
            'created_at' => $this->createdAt,
            'kind' => $this->kind,
            'path' => $this->getPath()
        ];
    }
}
