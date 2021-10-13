<?php


namespace App\Package\Marketing;

use DateTime;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use JsonSerializable;
use NumberFormatter;

class Total implements JsonSerializable
{
    /**
     * @var string $event
     */
    private $event;

    /**
     * @var int $count
     */
    private $count;

    /**
     * @var int $uniqueCount
     */
    private $uniqueCount;

    /**
     * @var int $percent
     */
    private $percent;

    /**
     * @var int $uniquePercent
     */
    private $uniquePercent;

    /**
     * @var Money $cashValue
     */
    private $cashValue;

    /**
     * @var Money $uniqueCashValue
     */
    private $uniqueCashValue;

    /**
     * @var DateTime $lastEvent
     */
    private $lastEvent;

    /**
     * @var IntlMoneyFormatter $moneyFormatter
     */
    private $moneyFormatter;

    /**
     * Total constructor.
     * @param string $event
     * @param int $count
     * @param int $uniqueCount
     */
    public function __construct(
        string $event,
        int $count,
        int $uniqueCount,
        int $lastEvent = 0
    ) {
        $this->event           = $event;
        $this->count    = $count;
        $this->uniqueCount     = $uniqueCount;
        $this->percent = 0;
        $this->uniquePercent = 0;
        $this->cashValue    = new Money(0, new Currency('GBP'));
        $this->uniqueCashValue    = new Money(0, new Currency('GBP'));
        $time = new DateTime();
        $this->lastEvent = $time->setTimestamp($lastEvent);
        $this->moneyFormatter = new IntlMoneyFormatter(
            new NumberFormatter('en_GB', NumberFormatter::CURRENCY),
            new ISOCurrencies()
        );
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getUniqueCount(): ?int
    {
        return $this->uniqueCount;
    }

    public function setCountPercent(int $percentOf)
    {
        if ($percentOf === 0) {
            return 0;
        };
        $this->percent = ($this->count / $percentOf) * 100;
    }

    public function setUniqueCountPercent(int $percentOf)
    {
        if ($percentOf === 0) {
            return 0;
        };
        $this->uniquePercent = ($this->uniqueCount / $percentOf) * 100;
    }

    public function getCountPercent()
    {
        return round($this->percent, 1);
    }

    public function getUniqueCountPercent()
    {
        return round($this->uniquePercent, 1);
    }

    public function getLastEvent(): DateTime
    {
        return $this->lastEvent;
    }

    public function getCashValue(): Money
    {
        return $this->cashValue;
    }

    public function getUniqueCashValue(): Money
    {
        return $this->uniqueCashValue;
    }

    public function setCashValue(int $totalSpend)
    {
        $this->cashValue = new Money($totalSpend * $this->getCount(), new Currency('GBP'));
        $this->uniqueCashValue = new Money($totalSpend * $this->getUniqueCount(), new Currency('GBP'));
    }



    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'name'                      => $this->getEvent(),
            'count'               => $this->getCount(),
            'unique_count'      => $this->getUniqueCount(),
            'count_percent' => $this->getCountPercent(),
            'unique_count_percent' => $this->getUniqueCountPercent(),
            'last_event_at' => $this->getLastEvent(),
            'cash_value' => $this->moneyFormatter->format($this->getCashValue()),
            'unique_cash_value' => $this->moneyFormatter->format($this->getUniqueCashValue())
        ];
    }
}
