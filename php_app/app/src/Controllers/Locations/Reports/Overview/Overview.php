<?php


namespace App\Controllers\Locations\Reports\Overview;

use DateInterval;
use Exception;
use JsonSerializable;
use DateTime;

/**
 * Class Overview
 * @package App\Controllers\Locations\Reports\Overview
 */
final class Overview implements JsonSerializable
{

    /**
     * @var DateTime $startDate
     */
    private $startDate;


    /**
     * @var DateTime $endDate
     */
    private $endDate;

    /**
     * @var SiteTotals[] $siteTotals
     */
    private $siteTotals = [];

    /**
     * Overview constructor.
     * @param DateTime $startDate
     * @param DateTime $endDate
     */
    public function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    /**
     * @param SiteTotals $siteTotals
     * @return self
     * @throws Exception
     */
    public function withSiteTotals(SiteTotals $siteTotals): self
    {
        $overview                                       = clone $this;
        $overview->siteTotals[$siteTotals->getSerial()] = $siteTotals;
        return $overview;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * @param string $serial
     * @return SiteTotals
     * @throws Exception
     */
    public function getTotalsForSerial(string $serial): ?SiteTotals
    {
        if (!array_key_exists($serial, $this->siteTotals)) {
            return null;
        }
        return $this->siteTotals[$serial];
    }

    /**
     * @return Totals
     * @throws Exception
     */
    public function getTotals(): Totals
    {
        $totals = new Totals();
        foreach ($this->siteTotals as $siteTotal) {
            $totals = $totals->withAdditionalTotals($siteTotal->getTotals());
        }
        return $totals;
    }

    /**
     * @return SiteTotals[]
     */
    public function getSiteTotals(): array
    {
        return $this->siteTotals;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function jsonSerialize()
    {
        return [
            'totals'    => $this->getTotals(),
            'locations' => array_values($this->getSiteTotals())
        ];
    }


}