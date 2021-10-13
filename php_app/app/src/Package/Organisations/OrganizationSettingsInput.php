<?php


namespace App\Package\Organisations;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class OrganizationSettingsInput
{
    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $input           = new self();
        $input->version  = Uuid::fromString($data['version'] ?? Uuid::NIL);
        $input->settings = OrganizationSettingsDefinition::fromArray($data['settings'] ?? []);
        return $input;
    }

    /**
     * @var UuidInterface $version
     */
    private $version;

    /**
     * @var OrganizationSettingsDefinition $settings
     */
    private $settings;

    /**
     * @return UuidInterface
     */
    public function getVersion(): UuidInterface
    {
        return $this->version;
    }

    /**
     * @return OrganizationSettingsDefinition
     */
    public function getSettings(): OrganizationSettingsDefinition
    {
        return $this->settings;
    }
}