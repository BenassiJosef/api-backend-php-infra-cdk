<?php

namespace App\Models\User;

use App\Models\UserProfile;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * UserAccount
 *
 * @ORM\Table(name="user_profile_mac_addresses")
 * @ORM\Entity
 * @package App\Models\User 
 */
class UserProfileMacAddress implements JsonSerializable
{

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="profile_id", type="string", nullable=false)
     * @var int $profileId
     */
    private $profileId;

    /**
     * @ORM\Column(name="mac_address", type="string", nullable=false)
     * @var string $macAddress
     */
    private $macAddress;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $macAddress
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\UserProfile", cascade={"persist"})
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=false)
     * @var UserProfile $profile
     */
    private $profile;

    public function __construct(UserProfile $profile, string $macAddress)
    {
        $this->id   = Uuid::uuid1();
        $this->profileId = $profile->getId();
        $this->profile = $profile;
        $this->macAddress = $macAddress;
        $this->createdAt = new DateTime();
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
    public function getMacAddress(): string
    {
        return $this->macAddress;
    }

    /**
     * @return int
     */
    public function getProfileId(): int
    {
        return $this->profileId;
    }

    /**
     * @return UserProfile
     */
    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'   => $this->getId()->toString(),
            'profile_id'  => $this->getProfileId(),
            'mac_address' => $this->getMacAddress(),
        ];
    }
}
