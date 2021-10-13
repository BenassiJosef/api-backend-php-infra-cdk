<?php


namespace App\Package\Loyalty\Reward;


use App\Models\Loyalty\LoyaltyReward;
use JsonSerializable;

class OutputReward implements JsonSerializable
{
    /**
     * @var Reward $reward
     */
    private $reward;

    /**
     * OutputReward constructor.
     * @param Reward $reward
     */
    public function __construct(Reward $reward)
    {
        $this->reward = $reward;
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
        switch ($this->reward->getType()) {
            case LoyaltyReward::TYPE_VALUE:
                return [
                    'id'             => $this->reward->getId()->toString(),
                    'organizationId' => $this->reward->getOrganizationId()->toString(),
                    'name'           => $this->reward->getName(),
                    'amount'         => $this->reward->getAmount(),
                    'currency'       => $this->reward->getCurrency(),
                    'type'           => $this->reward->getType(),
                    'createdAt'      => $this->reward->getCreatedAt(),
                ];
                break;
            case LoyaltyReward::TYPE_ITEM:
                return [
                    'id'             => $this->reward->getId()->toString(),
                    'organizationId' => $this->reward->getOrganizationId()->toString(),
                    'name'           => $this->reward->getName(),
                    'code'           => $this->reward->getCode(),
                    'type'           => $this->reward->getType(),
                    'createdAt'      => $this->reward->getCreatedAt(),
                ];
                break;
        }
    }
}