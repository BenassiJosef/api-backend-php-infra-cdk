<?php


namespace App\Controllers\Marketing\Campaign;

use JsonSerializable;
use voku\CssToInlineStyles\Exception;

class CampaignEligibilityMessage implements JsonSerializable
{
    const VERSION = 1;

    /**
     * @param array $data
     * @return static
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $message                   = new self();
        $version = $data['version'];
        if ($version !== self::VERSION) {
            throw new Exception("cannot read version(${version})");
        }
        $message->idempotencyToken = $data['idempotencyToken'];
        $message->userId           = $data['userId'];
        $message->campaignId       = $data['campaignId'];
        $message->serials          = $data['serials'];
        return $message;
    }

    /**
     * @var string $idempotencyToken
     */
    private $idempotencyToken;

    /**
     * @var string $userId
     */
    private $userId;

    /**
     * @var string $campaignId
     */
    private $campaignId;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * @return string
     */
    public function getIdempotencyToken(): string
    {
        return $this->idempotencyToken;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    /**
     * @return string[]
     */
    public function getSerials(): array
    {
        return $this->serials;
    }

    public function __toString()
    {
        $serials = implode(', ', $this->serials);
        return "{$this->userId} requested to send campaign {$this->campaignId} for serials {$serials}";
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'version'          => self::VERSION,
            'idempotencyToken' => $this->idempotencyToken,
            'userId'           => $this->userId,
            'campaignId'       => $this->campaignId,
            'serials'          => $this->serials,
        ];
    }
}