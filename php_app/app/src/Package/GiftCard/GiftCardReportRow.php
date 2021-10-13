<?php


namespace App\Package\GiftCard;


use JsonSerializable;
use Money\Currency;
use Money\Money;
use Ramsey\Uuid\UuidInterface;

class GiftCardReportRow implements JsonSerializable
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var UuidInterface $giftCardSettingsId
     */
    private $giftCardSettingsId;

    /**
     * @var Total[] $totals
     */
    private $totals;

    /**
     * GiftCardReportRow constructor.
     * @param string $name
     * @param UuidInterface $giftCardSettingsId
     * @param string $currency
     */
    public function __construct(
        string $name,
        UuidInterface $giftCardSettingsId,
        string $currency
    ) {
        $this->name               = $name;
        $this->giftCardSettingsId = $giftCardSettingsId;
        $this->zeroValuesForCurrency($currency);
    }

    private function zeroValuesForCurrency(string $currency)
    {
        $this->totals[$currency] = [
            'active'   => new Total('active', 0, $currency, 0),
            'unpaid'   => new Total('unpaid', 0, $currency, 0),
            'redeemed' => new Total('redeemed', 0, $currency, 0),
            'refunded' => new Total('refunded', 0, $currency, 0),
        ];
    }

    /**
     * @param string $total
     * @param int $totalAmount
     * @param string $currency
     * @param int $totalCards
     */
    public function updateTotal(string $total, int $totalAmount, string $currency, int $totalCards)
    {
        if (!array_key_exists($currency, $this->totals)) {
            $this->zeroValuesForCurrency($currency);
        }
        $this->totals[$currency][$total] = new Total($total, $totalAmount, $currency, $totalCards);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return UuidInterface
     */
    public function getGiftCardSettingsId(): UuidInterface
    {
        return $this->giftCardSettingsId;
    }

    /**
     * @return Total[]
     */
    public function getTotals(): array
    {
        $flatTotals = [];
        foreach ($this->totals as $currency => $types) {
            foreach ($types as $type => $total) {
                $flatTotals[] = $total;
            }
        }
        return $flatTotals;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'name'               => $this->getName(),
            'giftCardSettingsId' => $this->getGiftCardSettingsId(),
            'totals'             => $this->totals,
        ];
    }
}