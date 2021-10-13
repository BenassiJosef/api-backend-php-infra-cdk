<?php


namespace App\Package\Loyalty\Reward;

use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ItemLoyaltyRewardInput implements \JsonSerializable
{
    private static $requiredKeys = [
        'name'
    ];

    /**
     * @param array $data
     * @return self
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        foreach (self::$requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new Exception("${requiredKey} is a required key");
            }
        }
        $input                 = new self();
        $input->name           = $data['name'];
        $input->code           = $data['code'] ?? null;
        return $input;
    }

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string | null $code
     */
    private $code;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
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
            'name'           => $this->name,
            'code'           => $this->code,
        ];
    }
}
