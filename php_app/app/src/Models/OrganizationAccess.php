<?php


namespace App\Models;
use Doctrine\ORM\Mapping as ORM;

use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

/**
 * Class OrganizationAccess
 *
 * @ORM\Table(name="organization_access")
 * @ORM\Entity
 * @package App\Models
 */
class OrganizationAccess implements JsonSerializable
{
    /**
     * @ORM\Column(name="organization_id", type="uuid", nullable=false)
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization", inversedBy="access", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @ORM\Id
     * @ORM\Column(name="user_id", type="uuid")
     * @var UuidInterface $userId
     */
    public $userId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="uid", nullable=false)
     * @var OauthUser $user
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\Column(name="role_id", type="uuid")
     * @var UuidInterface $roleId
     */
    public $roleId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Role")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=false)
     * @var Role $role
     */
    private $role;

    /**
     * OrganizationAccess constructor.
     * @param Organization $organization
     * @param OauthUser $user
     * @param Role $role
     */
    public function __construct(Organization $organization, OauthUser $user, Role $role)
    {
        $this->organization   = $organization;
        $this->organizationId = $organization->getId();

        $this->user           = $user;
        $this->userId         = $user->getUid();

        $this->role           = $role;
        $this->roleId         = $role->getId();
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return OauthUser
     */
    public function getUser(): OauthUser
    {
        return $this->user;
    }

    /**
     * @return Role
     */
    public function getRole(): Role
    {
        return $this->role;
    }

    /**
     * @param Role $role
     * @return $this
     */
    public function setRole(Role $role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            "orgName" => $this->organization->getName(),
            "orgId" => $this->organizationId,
            "role" => $this->role->getName(),
            "legacyId" => $this->role->getLegacyId(),
            "email" => $this->user->getEmail(),
            "uid" => $this->userId,
            "first" => $this->user->getFirst(),
            "last" => $this->user->getLast()
        ];
    }
}