<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 24/10/2017
 * Time: 10:23
 */

namespace App\Models\Locations;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Vendors
 *
 * @ORM\Table(name="vendor_source")
 * @ORM\Entity
 */
class Vendors
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="key", type="string", nullable=false, unique=true)
     * @var string $name
     */
    private $key;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     * @var string $name
     */
    private $name;

    /**
     * @ORM\Column(name="auth_method", type="string", nullable=false)
     * @var string $name
     */
    private $authMethod;

    /**
     * @ORM\Column(name="radius", type="boolean", nullable=false)
     * @var bool $radius
     */
    private $radius;

    /**
     * DataSource constructor.
     * @param string $key
     * @param string $name
     * @throws Exception
     */
    public function __construct(
        string $key,
        string $name
    ) {
        $this->id = Uuid::uuid1();
        $this->key = $key;
        $this->name = $name;
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
    public function getKey(): string
    {
        return $this->key;
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
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }

    /**
     * @return bool
     */
    public function getRadius(): bool
    {
        return $this->radius;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId()->toString(),
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'radius' => $this->getRadius(),
            'auth_method' => $this->getAuthMethod(),
        ];
    }
}
