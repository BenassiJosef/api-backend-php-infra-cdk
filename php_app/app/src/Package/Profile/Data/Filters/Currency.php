<?php

namespace App\Package\Profile\Data\Filters;

use App\Package\Profile\Data\Filter;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

/**
 * Class Currency
 * @package App\Package\Profile\Data\Filters
 */
class Currency implements Filter
{
    /**
     * @var string $codeField
     */
    private $codeField;

    /**
     * @var string $amountField
     */
    private $amountField;

    /**
     * @var string $outputField
     */
    private $outputField;

    /**
     * Currency constructor.
     * @param string $codeField
     * @param string $amountField
     * @param string $outputField
     */
    public function __construct(
        string $codeField = 'currency',
        string $amountField = 'amount',
        string $outputField = 'amount'
    ) {
        $this->codeField   = $codeField;
        $this->amountField = $amountField;
        $this->outputField = $outputField;
    }

    /**
     * @param array $data
     * @return array
     */
    public function filter(array $data): array
    {
        $codeField = $this->codeField;
        if (!array_key_exists($codeField, $data)) {
            return $data;
        }
        $amountField = $this->amountField;
        if (!array_key_exists($amountField, $data)) {
            return $data;
        }
        $output                     = from($data)
            ->where(
                function ($v, $k) use ($codeField, $amountField): bool {
                    return !in_array($k, [$codeField, $amountField]);
                }
            )
            ->toArray();

        $code = $data[$codeField];
        $output[$this->outputField] = $this->format(
            new Money(
                $data[$amountField],
                new \Money\Currency($code)
            )
        );
        return $output;
    }

    /**
     * @param Money $money
     * @return string
     */
    private function format(Money $money): string
    {
        return (new IntlMoneyFormatter(
            new NumberFormatter('en_GB', NumberFormatter::CURRENCY),
            new ISOCurrencies()
        ))->format($money);
    }
}
