<?php


namespace App\Package\DataSources;

use DateTime;
use JsonSerializable;

class OptInStatuses implements JsonSerializable
{

    public static function optedIn()
    {
        return new self(true, true, true);
    }

    public static function fromArray(array $data): self
    {
        $statuses               = new self();
        $statuses->dataOptInAt  = self::boolToDateTime($data['dataOptIn'] ?? false);
        $statuses->emailOptInAt = self::boolToDateTime($data['emailOptIn'] ?? false);
        $statuses->smsOptInAt   = self::boolToDateTime($data['smsOptIn'] ?? false);
        return $statuses;
    }

    private static function boolToDateTime(bool $opted): ?DateTime
    {
        if ($opted) {
            return new DateTime();
        }
        return null;
    }

    private static function dateTimeToBool(?DateTime $opted): bool
    {
        if ($opted !== null) {
            return true;
        }
        return false;
    }

    /**
     * OptInStatuses constructor.
     * @param bool $dataOptIn
     * @param bool $emailOptIn
     * @param bool $smsOptIn
     */
    public function __construct(bool $dataOptIn = false, bool $emailOptIn = false, bool $smsOptIn = false)
    {
        $this->dataOptInAt  = self::boolToDateTime($dataOptIn);
        $this->emailOptInAt = self::boolToDateTime($emailOptIn);
        $this->smsOptInAt   = self::boolToDateTime($smsOptIn);
    }

    /**
     * @var DateTime | null $dataOptInAt
     */
    private $dataOptInAt;

    /**
     * @var DateTime | null $emailOptInAt
     */
    private $emailOptInAt;

    /**
     * @var DateTime | null $smsOptInAt
     */
    private $smsOptInAt;


    /**
     * @return DateTime|null
     */
    public function getDataOptInAt(): ?DateTime
    {
        return $this->dataOptInAt;
    }

    public function getDataOptInAtString(): ?string
    {
        if ($this->getDataOptInAt() === null) {
            return null;
        }
        return $this->dataOptInAt->format('Y-m-d H:i:s');
    }

    /**
     * @return DateTime|null
     */
    public function getEmailOptInAt(): ?DateTime
    {
        return $this->emailOptInAt;
    }

    public function getEmailOptInAtString(): ?string
    {
        if ($this->emailOptInAt === null) {
            return null;
        }
        return $this->emailOptInAt->format('Y-m-d H:i:s');
    }

    /**
     * @return DateTime|null
     */
    public function getSmsOptInAt(): ?DateTime
    {
        return $this->smsOptInAt;
    }

    public function getSmsOptInAtString(): ?string
    {
        if ($this->smsOptInAt === null) {
            return null;
        }
        return $this->smsOptInAt->format('Y-m-d H:i:s');
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'data_opt_in_at'  => $this->getDataOptInAtString(),
            'email_opt_in_at' => $this->getEmailOptInAtString(),
            'sms_opt_in_at'   => $this->getSmsOptInAtString(),
        ];
    }
}