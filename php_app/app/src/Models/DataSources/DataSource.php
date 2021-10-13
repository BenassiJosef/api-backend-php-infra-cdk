<?php


namespace App\Models\DataSources;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class DataSource
 *
 * @ORM\Table(name="data_source")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class DataSource implements JsonSerializable
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
     * @ORM\Column(name="is_visit", type="boolean", nullable=false)
     * @var bool $isVisit
     */
    private $isVisit;

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
        $this->id   = Uuid::uuid1();
        $this->key  = $key;
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
     * @return bool
     */
    public function isVisit(): bool
    {
        return $this->isVisit;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'      => $this->getId()->toString(),
            'key'     => $this->getKey(),
            'name'    => $this->getName(),
            'isVisit' => $this->isVisit(),
        ];
    }
}