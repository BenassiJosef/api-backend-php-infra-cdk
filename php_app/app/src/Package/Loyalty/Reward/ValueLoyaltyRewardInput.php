<?php


namespace App\Package\Loyalty\Reward;

use Exception;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ValueLoyaltyRewardInput implements JsonSerializable
{
    private static $requiredKeys = [
        'name',
        'amount',
    ];

    public static function fromArray(array $data): self
    {
        foreach (self::$requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new Exception("${requiredKey} is a required key");
            }
        }
        $input                 = new self();
        $input->name           = $data['name'];
        $input->amount         = $data['amount'];
        $input->currency       = $data['currency'] ?? 'GBP';
        return $input;
    }

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var int $amount
     */
    private $amount;

    /**
     * @var string $currency
     */
    private $currency;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
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
            'amount'         => $this->amount,
            'currency'       => $this->currency,
        ];
    }
}
