<?php


namespace App\Package\Loyalty\App;


use App\Models\Organization;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JsonSerializable;

class LoyaltyOrganization implements JsonSerializable
{

    /**
     * @param Organization $organization
     * @return LoyaltyOrganization
     */
    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            $organization->getId(),
            $organization->getName()
        );
    }

    /**
     * @param array $data
     * @return LoyaltyOrganization
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Uuid::fromString($data['organizationId']),
            $data['organizationName']
        );
    }

    /**
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * LoyaltyOrganization constructor.
     * @param UuidInterface $id
     * @param string $name
     */
    public function __construct(
        UuidInterface $id,
        string $name
    ) {
        $this->id   = $id;
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
    public function getName(): string
    {
        return $this->name;
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
            'id'   => $this->id->toString(),
            'name' => $this->name,
        ];
    }

}