<?php

namespace App\Package\Reviews\Reports;

use App\Package\Reports\Time;
use JsonSerializable;

class ReportRow implements JsonSerializable
{

    /**
     * @var Totals $totals
     */
    private $totals;

    /**
     * @var ReviewTotals[] $chartTotals
     */
    private $chartTotals;

    /**
     * @var ReviewTotals[] $pageTotals
     */
    private $pageTotals;

    /**
     * @var ReviewTotals[] $platformTotals
     */
    private $platformTotals;

    public function __construct()
    {
        $this->chartTotals = [];
        $this->pageTotals = [];
        $this->platformTotals = [];
        $this->totals = new ReviewTotals(
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            0,
            null
        );
    }

    public function updateTotal(
        string $key,
        string $pageId,
        string $platform,
        Time $time,
        int $users,
        int $reviews,
        int $oneStar,
        int $twoStar,
        int $threeStar,
        int $fourStar,
        int $fiveStar,
        int $mixedSentiment,
        int $neutralSentiment,
        int $negativeSentiment,
        int $positiveSentiment,
        int $done,
        int $google,
        int $facebook,
        int $tripadvisor,
        int $stampede
    ) {

        $this->totals->updateTotal(
            $users,
            $reviews,
            $oneStar,
            $twoStar,
            $threeStar,
            $fourStar,
            $fiveStar,
            $mixedSentiment,
            $neutralSentiment,
            $negativeSentiment,
            $positiveSentiment,
            $done,
            $google,
            $facebook,
            $tripadvisor,
            $stampede,
        );

        if (array_key_exists($platform, $this->platformTotals)) {
            $this->platformTotals[$platform]->updateTotal(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
            );
        } else {
            $this->platformTotals[$platform] = new ReviewTotals(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
                $time
            );
        }

        if (array_key_exists($time->getYearMonth(), $this->chartTotals)) {
            $this->chartTotals[$time->getYearMonth()]->updateTotal(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
            );
        } else {
            $this->chartTotals[$time->getYearMonth()] = new ReviewTotals(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
                $time
            );
        }
        if (array_key_exists($pageId, $this->pageTotals)) {
            $this->pageTotals[$pageId]->updateTotal(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
            );
        } else {
            $this->pageTotals[$pageId] = new ReviewTotals(
                $users,
                $reviews,
                $oneStar,
                $twoStar,
                $threeStar,
                $fourStar,
                $fiveStar,
                $mixedSentiment,
                $neutralSentiment,
                $negativeSentiment,
                $positiveSentiment,
                $done,
                $google,
                $facebook,
                $tripadvisor,
                $stampede,
                $time
            );
        }
    }



    /**
     * @return array
     */
    public function getChartTotals(): array
    {
        $flatTotals = [];
        foreach ($this->chartTotals as $total) {
            $flatTotals[] = $total->jsonSerializeChart();
        }
        return $flatTotals;
    }

    /**
     * @return array
     */
    public function getPageTotals(): array
    {
        $flatTotals = [];
        foreach ($this->pageTotals as $total) {
            $flatTotals[] = $total->jsonSerializeChart();
        }
        return $flatTotals;
    }

    /**
     * @return array
     */
    public function getPlatformTotals(): array
    {
        $flatTotals = [];
        foreach ($this->platformTotals as $total) {
            $flatTotals[] = $total->jsonSerializeChart();
        }
        return $flatTotals;
    }


    public function jsonSerialize()
    {
        return [
            'chart' => $this->getChartTotals(),
            'totals' => $this->totals->jsonSerialize(),
            'page_totals' => $this->pageTotals,
            'platform_totals' => $this->platformTotals
        ];
    }
}
