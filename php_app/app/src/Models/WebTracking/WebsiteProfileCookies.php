<?php


namespace App\Models\WebTracking;

use App\Models\UserProfile;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use DateTime;
use Exception;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Twig\Profiler\Profile;

/**
 * WebsiteProfileCookies
 *
 * @ORM\Table(name="website_profile_cookies")
 * @ORM\Entity
 */
class WebsiteProfileCookies implements JsonSerializable
{

    /**
     * @ORM\Id
     * @ORM\Column(name="cookie_id", type="string")
     * @var string $cookieId
     */
    private $cookieId;

    /**
     * @ORM\Column(name="profile_id", type="string")
     * @var string $profileId
     */
    private $profileId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\UserProfile", cascade={"persist"})
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=false)
     * @var UserProfile $profile
     */
    private $profile;

    /**
     * @ORM\Column(name="visits", type="string")
     * @var int $visits
     */
    private $visits;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="lastvisit_at", type="datetime", nullable=false)
     * @var DateTime $lastvisitAt
     */
    private $lastvisitAt;

    /**
     * WebsiteProfileCookies constructor.
     * @param UserProfile $profile
     * @param string $cookieId
     * @throws Exception
     */
    public function __construct(
        UserProfile $profile,
        string $cookieId
    ) {
        $this->profileId = $profile->getId();
        $this->profile = $profile;
        $this->cookieId = $cookieId;
        $this->createdAt = new DateTime();
        $this->lastvisitAt = new DateTime();
        $this->visits = 1;
    }

    /**
     * @return string
     */
    public function getCookieId(): string
    {
        return $this->cookieId;
    }

    /**
     * @param string $cookieId
     * @return string
     */
    public function setCookieId(string $cookieId): string
    {
        $this->cookieId = $cookieId;

        return $this->cookieId;
    }

    /**
     * @param int $profileId
     * @return int
     */
    public function setProfileId(int $profileId): int
    {
        $this->profileId = $profileId;

        return $this->profileId;
    }

    /**
     * @return int
     */
    public function getVisits(): int
    {
        if (is_null($this->visits)) {
            return 0;
        }
        return $this->visits;
    }

    /**
     * @param int $visit
     * @return int
     */
    public function setVisits(int $visit): int
    {
        $this->visits = $visit;

        return $this->visits;
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
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return DateTime
     */
    public function getLastvisitAt(): ?DateTime
    {
        return $this->lastvisitAt;
    }


    public function jsonSerialize()
    {
        return [
            "profileId" => $this->getProfileId(),
            "cookieId" => $this->getCookieId(),
            "createdAt" => $this->getCreatedAt(),
            "profile" => [
                "email" => $this->profile->getEmail(),
                "first" => $this->profile->getFirst(),
                "last" => $this->profile->getLast()
            ]
        ];
    }
}
