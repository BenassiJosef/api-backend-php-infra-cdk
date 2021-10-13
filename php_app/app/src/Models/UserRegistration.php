<?php


namespace App\Models;

use DateTime;
use Exception;
use Doctrine\ORM\Mapping as ORM;
/**
 * Class UserRegistrations
 * @package App\Models
 *
 * @ORM\Table(name="user_registrations", indexes={
 *          @ORM\Index(name="profile_id_idx", columns={"profile_id"}),
 *          @ORM\Index(name="created_at_idx", columns={"created_at"}),
 *          @ORM\Index(name="last_seen_at_idx", columns={"last_seen_at"})
 *     })
 * @ORM\Entity
 */
class UserRegistration
{
    /**
     * @var string $serial
     * @ORM\Id
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     */
    private $serial;

    /**
     * @var integer $profileId
     * @ORM\Column(name="profile_id", type="integer", nullable=false)
     */
    private $profileId;

    /**
     * @var integer $numberOfVisits
     * @ORM\Column(name="number_of_visits", type="integer", nullable=false)
     */
    private $numberOfVisits;

    /**
     * @var DateTime $createdAt
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var DateTime $lastSeenAt
     * @ORM\Column(name="last_seen_at", type="datetime", nullable=false)
     */
    private $lastSeenAt;

    /**
     * @var DateTime $emailOptInDate
     * @ORM\Column(name="email_opt_in_date", type="datetime", nullable=true)
     */
    private $emailOptInDate;

    /**
     * @var DateTime $smsOptInDate
     * @ORM\Column(name="sms_opt_in_date", type="datetime", nullable=true)
     */
    private $smsOptInDate;

    /**
     * @var DateTime $locationOptInDate
     * @ORM\Column(name="location_opt_in_date", type="datetime", nullable=true)
     */
    private $locationOptInDate;

    /**
     * UserRegistrations constructor.
     * @param string $serial
     * @param int $profileId
     * @param DateTime|null $createdAt
     * @param DateTime|null $lastSeenAt
     * @param int $numberOfVisits
     * @throws Exception
     */
    public function __construct(
        string $serial,
        int $profileId,
        DateTime $createdAt = null,
        DateTime $lastSeenAt = null,
        int $numberOfVisits = 1
    ) {
        $this->serial         = $serial;
        $this->profileId      = $profileId;
        $this->numberOfVisits = $numberOfVisits;
        $now                  = new DateTime('now');
        $this->createdAt      = $createdAt ?? $now;
        $this->lastSeenAt     = $lastSeenAt ?? $now;
        $this->emailOptInDate    = $now;
        $this->smsOptInDate      = $now;
        $this->locationOptInDate = $now;
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        return $this->serial;
    }

    /**
     * @return int
     */
    public function getProfileId(): int
    {
        return $this->profileId;
    }

    /**
     * @return int
     */
    public function getNumberOfVisits(): int
    {
        return $this->numberOfVisits;
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
    public function getLastSeenAt(): DateTime
    {
        return $this->lastSeenAt;
    }

    /**
     * @return DateTime
     */
    public function getEmailOptInDate(): DateTime
    {
        return $this->emailOptInDate;
    }

    /**
     * @param DateTime|null $emailOptInDate
     * @return UserRegistration
     */
    public function setEmailOptInDate(?DateTime $emailOptInDate): UserRegistration
    {
        $this->emailOptInDate = $emailOptInDate;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSmsOptInDate(): DateTime
    {
        return $this->smsOptInDate;
    }

    /**
     * @param DateTime|null $smsOptInDate
     * @return UserRegistration
     */
    public function setSmsOptInDate(?DateTime $smsOptInDate): UserRegistration
    {
        $this->smsOptInDate = $smsOptInDate;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLocationOptInDate(): DateTime
    {
        return $this->locationOptInDate;
    }

    /**
     * @param DateTime|null $locationOptInDate
     * @return UserRegistration
     */
    public function setLocationOptInDate(?DateTime $locationOptInDate): UserRegistration
    {
        $this->locationOptInDate = $locationOptInDate;

        return $this;
    }

    public function getSMSOptOut()
    {
        return $this->smsOptInDate == null;
    }

    public function getEmailOptOut()
    {
        return $this->emailOptInDate == null;
    }

    public function getLocationOptOut()
    {
        return $this->locationOptInDate == null;
    }
}