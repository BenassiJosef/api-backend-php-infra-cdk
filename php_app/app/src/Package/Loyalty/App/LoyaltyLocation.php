<?php


namespace App\Package\Loyalty\App;

use App\Models\Locations\LocationSettings;
use JsonSerializable;

class LoyaltyLocation implements JsonSerializable
{
    public static function fromLocationSettings(LocationSettings $locationSettings): self
    {
        return new self(
            $locationSettings->getSerial(),
            $locationSettings->getAlias()
        );
    }

    /**
     * @param array $data
     * @return LoyaltyLocation
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['serial'],
            $data['name'] ?? null
        );
    }

    /**
     * @var string $serial
     */
    private $serial;

    /**
     * @var string | null $name
     */
    private $name;

    /**
     * LoyaltyLocation constructor.
     * @param string $serial
     * @param string $name
     */
    public function __construct(
        string $serial,
        ?string $name = null
    ) {
        $this->serial = $serial;
        $this->name   = $name;
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        return $this->serial;
    }

    /**
     * @return string | null
     */
    public function getName(): ?string
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
            'serial' => $this->serial,
            'name'   => $this->name,
        ];
    }
}