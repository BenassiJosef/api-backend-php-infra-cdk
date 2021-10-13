<?php

namespace App\Package\Marketing;

use JsonSerializable;

class MarketingReportRow implements JsonSerializable
{

    /**
     * @var Total[] $totals
     */
    private $totals;

    public function __construct()
    {
        $this->totals = $this->zeroValues();
    }

    private function zeroValues()
    {
        return [
            'bounce'   => new Total('bounce', 0, 0),
            'click'   => new Total('click', 0, 0),
            'delivered' => new Total('delivered', 0, 0),
            'open' => new Total('open', 0, 0),
            'dropped' => new Total('dropped', 0, 0),
            'processed' => new Total('processed', 0, 0),
            'in_venue_visit' => new Total('in_venue_visit', 0, 0),
            'opt_out' => new Total('opt_out', 0, 0),
        ];
    }

    /**
     * @param string $total
     * @param int $totalAmount
     * @param string $currency
     * @param int $totalCards
     */
    public function updateTotal(string $event, int $count, int $uniqueCount, int $lastEvent)
    {
        $this->totals[$event] = new Total($event, $count, $uniqueCount, $lastEvent);
    }

    public function updateInVenueSpend(int $spendPerHead)
    {
        $this->totals['in_venue_visit']->setCashValue($spendPerHead);
    }

    /**
     * @return Total[]
     */
    public function getPercents(): array
    {
        $processed = $this->totals['processed'];
        $deliveres = $this->totals['delivered'];
        $opens = $this->totals['open'];
        $clicks = $this->totals['click'];
        $visits = $this->totals['in_venue_visit'];
        $dropped = $this->totals['dropped'];
        $bounce = $this->totals['bounce'];
        $opt_out = $this->totals['opt_out'];

        $this->setPercent($deliveres, $processed);
        $this->setPercent($dropped, $deliveres);
        $this->setPercent($bounce, $deliveres);
        $this->setPercent($visits, $deliveres);
        $this->setPercent($opens, $deliveres);
        $this->setPercent($clicks, $opens);
        $this->setPercent($opt_out, $deliveres);

        return $this->totals;
    }

    /**
     * @return Total[]
     */
    public function getTotals(): array
    {


        return $this->getPercents();
        /*
        $flatTotals = [];
        foreach ($this->getPercents() as $event) {
            $flatTotals[] = $event;
        }
        return $flatTotals;
*/
    }

    public function setPercent(Total $total, Total $comparitor)
    {
        $total->setCountPercent($comparitor->getCount());
        $total->setUniqueCountPercent($comparitor->getUniqueCount());
    }

    public function jsonSerialize()
    {
        return $this->getTotals();
    }
}
