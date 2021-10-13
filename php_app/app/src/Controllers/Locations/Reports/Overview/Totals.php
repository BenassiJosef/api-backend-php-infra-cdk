<?php


namespace App\Controllers\Locations\Reports\Overview;

use Exception;
use DateInterval;
use DateTime;
use JsonSerializable;

/**
 * Class Totals
 * @package App\Controllers\Locations\Reports\Overview
 */
final class Totals implements JsonSerializable
{
    /**
     * @var int $returningUsers
     */
    private $returningUsers;

    /**
     * @var int $newUsers
     */
    private $newUsers;

    /**
     * @var int $dwellTime
     */
    private $dwellTime;

    /**
     * @var int $splashImpressions
     */
    private $splashImpressions;

    /**
     * @var int $connections
     */
    private $connections;

    /**
     * @var ReviewTotals|null $reviewTotals
     */
    private $reviewTotals;

    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $totals = new self();
        return $totals
            ->withAdditionalReturningUsers($data['returning_users'] ?? 0)
            ->withAdditionalNewUsers($data['new_users'] ?? 0)
            ->withAdditionalDwellTime($data['dwell_time'] ?? 0)
            ->withAdditionalSplashImpressions($data['splash_impressions'] ?? 0)
            ->withAdditionalConnections($data['connections'] ?? 0);
    }

    /**
     * Totals constructor.
     * @param int $returningUsers
     * @param int $newUsers
     * @param DateInterval|null $dwellTime
     * @param int $splashImpressions
     * @param int $numberOfConnections
     * @throws Exception
     */
    public function __construct(
        int $returningUsers = 0,
        int $newUsers = 0,
        int $dwellTime = 0,
        int $splashImpressions = 0,
        int $numberOfConnections = 0
    ) {
        $this->returningUsers    = $returningUsers;
        $this->newUsers          = $newUsers;
        $this->dwellTime         = $dwellTime;
        $this->splashImpressions = $splashImpressions;
        $this->connections       = $numberOfConnections;
    }

    /**
     * @param Totals $additionalTotals
     * @return $this
     * @throws Exception
     */
    public function withAdditionalTotals(Totals $additionalTotals): self
    {
        return $this
            ->withAdditionalReturningUsers($additionalTotals->getReturningUsers())
            ->withAdditionalNewUsers($additionalTotals->getNewUsers())
            ->withAdditionalDwellTime($additionalTotals->getDwellTime())
            ->withAdditionalSplashImpressions($additionalTotals->getSplashImpressions())
            ->withAdditionalConnections($additionalTotals->getConnections())
            ->withAdditionalReviewsTotals($additionalTotals->getReviewTotals());
    }

    /**
     * @param int $returningUsers
     * @return $this
     */
    public function withAdditionalReturningUsers(int $returningUsers): self
    {
        $totals                 = clone $this;
        $totals->returningUsers += $returningUsers;
        return $totals;
    }

    /**
     * @param int $newUsers
     * @return $this
     */
    public function withAdditionalNewUsers(int $newUsers): self
    {
        $totals           = clone $this;
        $totals->newUsers += $newUsers;
        return $totals;
    }

    /**
     * @param int $dwellTime
     * @return $this
     */
    public function withAdditionalDwellTime(int $dwellTime): self
    {
        $totals            = clone $this;
        $totals->dwellTime += $dwellTime;
        return $totals;
    }

    /**
     * @param int $splashImpressions
     * @return $this
     */
    public function withAdditionalSplashImpressions(int $splashImpressions): self
    {
        $totals                    = clone $this;
        $totals->splashImpressions += $splashImpressions;
        return $totals;
    }

    /**
     * @param int $connections
     * @return $this
     */
    public function withAdditionalConnections(int $connections): self
    {
        $totals              = clone $this;
        $totals->connections += $connections;
        return $totals;
    }

    /**
     * @param ReviewTotals $reviewTotals
     * @return $this
     */
    public function withAdditionalReviewsTotals(?ReviewTotals $reviewTotals): self
    {
        if ($reviewTotals === null){
            return $this;
        }
        $totals = clone $this;
        if ($this->reviewTotals === null){
            $totals->reviewTotals = $reviewTotals;
            return $totals;
        }
        $totals->reviewTotals = $this
            ->reviewTotals
            ->withAdditionalReviewsTotals($reviewTotals);
        return $totals;
    }

    /**
     * @return int
     */
    public function getReturningUsers(): int
    {
        return $this->returningUsers;
    }

    /**
     * @return int
     */
    public function getNewUsers(): int
    {
        return $this->newUsers;
    }

    /**
     * @return int
     */
    public function getTotalUsers(): int
    {
        return $this->returningUsers + $this->newUsers;
    }

    /**
     * @return int
     */
    public function getDwellTime(): int
    {
        return $this->dwellTime;
    }

    /**
     * @return int
     */
    public function getAverageDwellTime(): int
    {
        if ($this->getConnections() === 0) {
            return 0;
        }
        return (int)($this->getDwellTime() / $this->getConnections());
    }

    /**
     * @return int
     */
    public function getSplashImpressions(): int
    {
        return $this->splashImpressions;
    }

    /**
     * @return int
     */
    public function getConnections(): int
    {
        return $this->connections;
    }

    /**
     * @return ReviewTotals|null
     */
    public function getReviewTotals(): ?ReviewTotals
    {
        return $this->reviewTotals;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'returningUsers'    => $this->getReturningUsers(),
            'newUsers'          => $this->getNewUsers(),
            'totalUsers'        => $this->getTotalUsers(),
            'dwellTime'         => $this->getDwellTime(),
            'averageDwellTime'  => $this->getAverageDwellTime(),
            'splashImpressions' => $this->getSplashImpressions(),
            'connections'       => $this->getConnections(),
            'reviewTotals'      => $this->getReviewTotals() ?? new ReviewTotals(),
        ];
    }
}