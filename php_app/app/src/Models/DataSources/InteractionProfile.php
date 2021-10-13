<?php


namespace App\Models\DataSources;

use App\Models\UserProfile;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class InteractionProfile
 *
 * @ORM\Table(name="interaction_profile")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class InteractionProfile implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", nullable=false)
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="interaction_id", type="uuid", nullable=false)
     * @var UuidInterface $interactionId
     */
    private $interactionId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\DataSources\Interaction", inversedBy="profiles", cascade={"persist"})
     * @ORM\JoinColumn(name="interaction_id", referencedColumnName="id", nullable=false)
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @ORM\Column(name="profile_id", type="integer", nullable=false)
     * @var int $profileId
     */
    private $profileId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\UserProfile", cascade={"persist"})
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=false)
     * @var UserProfile $profile
     */
    private $profile;

    /**
     * InteractionProfile constructor.
     * @param Interaction $interaction
     * @param UserProfile $profile
     * @throws Exception
     */
    public function __construct(Interaction $interaction, UserProfile $profile)
    {
        $this->id            = Uuid::uuid1();
        $this->interactionId = $interaction->getId();
        $this->interaction   = $interaction;
        $this->profileId     = $profile->getId();
        $this->profile       = $profile;
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getInteractionId(): UuidInterface
    {
        return $this->interactionId;
    }

    /**
     * @return Interaction
     */
    public function getInteraction(): Interaction
    {
        return $this->interaction;
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
            'id'            => $this->getId(),
            'interactionId' => $this->getInteractionId(),
            'profileId'     => $this->getProfileId(),
            'profile' => $this->getProfile()->jsonSerialize(),
            'interaction' => $this->getInteraction()->jsonSerialize()
        ];
    }
}
