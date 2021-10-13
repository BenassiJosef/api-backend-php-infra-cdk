<?php


namespace App\Package\Member;


use App\Models\Organization;
use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

class MinimalPresentationOrganization implements JsonSerializable
{
    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * MinimalPresentationOrganization constructor.
     * @param Organization $organization
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function getId(): UuidInterface
    {
        return $this->organization->getId();
    }

    public function getParentOrganizationId(): ?UuidInterface
    {
        return $this->organization->getParentOrganizationId();
    }

    public function getName(): string
    {
        return $this->organization->getName();
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
            'id'                   => $this->getId()->toString(),
            'parentOrganizationId' => $this->getParentOrganizationId(),
            'name'                 => $this->getName(),
        ];
    }
}