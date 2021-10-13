<?php


namespace App\Package\GiftCard;


use App\Models\GiftCard;
use App\Models\GiftCardSettings;
use App\Package\DataSources\CandidateProfile;
use Exception;
use Stripe\JsonSerializable;
use Throwable;

class GiftCardCreationInput implements JsonSerializable
{
    /**
     * @var CandidateProfile $candidateProfile
     */
    private $candidateProfile;

    /**
     * @var int $amount
     */
    private $amount;

    /**
     * @var string $currency
     */
    private $currency;

    /**
     * @param array $input
     * @return static
     */
    public static function createFromArray(array $input): self
    {
        $candidateProfile = CandidateProfile::fromArray($input);
        return new self(
            $candidateProfile,
            $input['amount'] ?? 0,
            $input['currency'] ?? 'GBP'
        );
    }

    /**
     * GiftCardCreationInput constructor.
     * @param CandidateProfile $candidateProfile
     * @param int $amount
     * @param string $currency
     */
    public function __construct(
        CandidateProfile $candidateProfile,
        int $amount,
        string $currency = 'GBP'
    ) {
        $this->candidateProfile = $candidateProfile;
        $this->amount           = $amount;
        $this->currency         = $currency;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->candidateProfile->getEmail();
    }

    /**
     * @return string
     */
    public function getFirst(): string
    {
        return $this->candidateProfile->getFirst();
    }

    /**
     * @return string
     */
    public function getLast(): string
    {
        return $this->candidateProfile->getLast();
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
     * @return CandidateProfile
     */
    public function getCandidateProfile(): CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function jsonSerialize()
    {
        return array_merge(
            $this->candidateProfile->jsonSerialize(),
            [
                'amount'   => $this->amount,
                'currency' => $this->currency,
            ]
        );
    }

}