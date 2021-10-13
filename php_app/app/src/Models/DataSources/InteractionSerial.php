<?php


namespace App\Models\DataSources;

use Doctrine\ORM\Mapping as ORM;
use App\Models\Locations\LocationSettings;
use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class InteractionSerial
 *
 * @ORM\Table(name="interaction_serial")
 * @ORM\Entity
 * @package App\Models\DataSources
 */
class InteractionSerial implements JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="App\Models\DataSources\Interaction", inversedBy="serials", cascade={"persist"})
     * @ORM\JoinColumn(name="interaction_id", referencedColumnName="id", nullable=false)
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @ORM\Column(name="serial", type="string", length=12, nullable=false)
     * @var string $serial
     */
    private $serial;

    /**
     * InteractionSerial constructor.
     * @param Interaction $interaction
     * @param LocationSettings $locationSettings
     * @throws Exception
     */
    public function __construct(
        Interaction $interaction,
        LocationSettings $locationSettings
    ) {
        $this->id               = Uuid::uuid1();
        $this->interactionId    = $interaction->getId();
        $this->interaction      = $interaction;
        $this->serial           = $locationSettings->getSerial();
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
     * @return string
     */
    public function getSerial(): string
    {
        return $this->serial;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id'            => $this->getId(),
            'interactionId' => $this->getInteractionId(),
            'serial'        => $this->getSerial(),
        ];
    }
}