<?php


namespace App\Package\GiftCard;


use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use JsonSerializable;
use NumberFormatter;

class Total implements JsonSerializable
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var Money $totalAmount
     */
    private $totalAmount;

    /**
     * @var int $totalCards
     */
    private $totalCards;

    /**
     * @var IntlMoneyFormatter $moneyFormatter
     */
    private $moneyFormatter;

    /**
     * Total constructor.
     * @param string $name
     * @param int $totalAmount
     * @param string $currency
     * @param int $totalCards
     */
    public function __construct(
        string $name,
        int $totalAmount,
        string $currency,
        int $totalCards
    ) {
        $this->name           = $name;
        $this->totalAmount    = new Money($totalAmount, new Currency($currency));
        $this->totalCards     = $totalCards;
        $this->moneyFormatter = new IntlMoneyFormatter(
            new NumberFormatter('en_GB', NumberFormatter::CURRENCY),
            new ISOCurrencies()
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Money
     */
    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    /**
     * @return int
     */
    public function getTotalCards(): int
    {
        return $this->totalCards;
    }

    /**
     * @return Money
     */
    public function getAverageCardValue(): Money
    {
        if ($this->totalCards === 0) {
            return new Money(0, $this->totalAmount->getCurrency());
        }
        return $this->totalAmount->divide($this->totalCards);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'name'                      => $this->getName(),
            'totalAmount'               => $this->getTotalAmount(),
            'formattedTotalAmount'      => $this->moneyFormatter->format($this->getTotalAmount()),
            'averageCardValue'          => $this->getAverageCardValue(),
            'formattedAverageCardValue' => $this->moneyFormatter->format($this->getAverageCardValue()),
            'totalCards'                => $this->getTotalCards(),
        ];
    }


}