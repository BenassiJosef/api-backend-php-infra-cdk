<?php


namespace App\Models\Forms;

use App\Models\Organization;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use DateTime;

/**
 * Website
 *
 * @ORM\Table(name="forms")
 * @ORM\Entity
 */
class Forms implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @ORM\Column(name="organization_id", type="uuid")
     * @var UuidInterface $organizationId
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

    /**
     * @ORM\Column(name="name", type="string")
     * @var string name
     */
    private $name;

    /**
     * @ORM\Column(name="redirect", type="string")
     * @var string redirect
     */
    private $redirect;

    /**
     * @ORM\Column(name="inputs", type="json")
     * @var array inputs
     */
    private $inputs;

    /**
     * @ORM\Column(name="serials", type="json")
     * @var array serials
     */
    private $serials;

    /**
     * @ORM\Column(name="colour", type="string")
     * @var string colour
     */
    private $colour;

    /**
     * @ORM\Column(name="opt_text", type="string")
     * @var string optText
     */
    private $optText;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @var DateTime $createdAt
     */
    private $createdAt;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=false)
     * @var DateTime $deletedAt
     */
    private $deletedAt;

    /**
     * @var boolean $validSubscription
     */
    private $validSubscription = true;

    /**
     * Website constructor.
     * @param Organization $organization
     * @param string $name
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string $name
    ) {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->organizationId = $organization->getId();
        $this->organization = $organization;
        $this->createdAt = new DateTime();
        $this->serials = [];
    }

    /**
     * @param string $name
     * @return string
     */
    public function setName(string $name): string
    {
        $this->name = $name;

        return $this->name;
    }

    /**
     * @param string $color
     * @return string
     */
    public function setColour(string $color): string
    {
        $this->colour = $color;

        return $this->colour;
    }

    /**
     * @param string $text
     * @return string
     */
    public function setOptText(string $text): string
    {
        $this->optText = $text;

        return $this->optText;
    }

    /**
     * @param array $inputs
     * @return array
     */
    public function setInputs(array $inputs): array
    {
        $this->inputs = $inputs;

        return $this->inputs;
    }

    /**
     * @param array $serials
     * @return array
     */
    public function setSerials(array $serials): array
    {
        $this->serials = $serials;

        return $this->serials;
    }

    /**
     * @param string $redirect
     * @return string
     */
    public function setRedirect(string $redirect): ?string
    {
        $this->redirect = $redirect;

        return $this->redirect;
    }

    /**
     * @param bool $deleted
     * @return DateTime|null
     * @throws Exception
     */
    public function setDeleted(bool $deleted): ?DateTime
    {
        if ($deleted) {
            $this->deletedAt = new DateTime();
        } else {
            $this->deletedAt = null;
        }

        return $this->deletedAt;
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
     * @return string
     */
    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    /**
     * @return string
     */
    public function getColour(): ?string
    {
        return $this->colour;
    }

    /**
     * @return string
     */
    public function getOptText(): ?string
    {
        return $this->optText;
    }

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @return array
     */
    public function getSerials(): array
    {
        return $this->serials;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setValidSubscription(bool $validSubscription)
    {
        $this->validSubscription = $validSubscription;
    }


    public function jsonSerialize()
    {
        return [
            "id" => $this->getId(),
            "orgId" => $this->getOrganizationId(),
            "name" => $this->getName(),
            "createdAt" => $this->getCreatedAt(),
            "redirect" => $this->getRedirect(),
            "inputs" => $this->getInputs(),
            "colour" => $this->getColour(),
            "optText" => $this->getOptText(),
            "serials" => $this->getSerials(),
            'valid_subscription' => $this->validSubscription
        ];
    }
}
