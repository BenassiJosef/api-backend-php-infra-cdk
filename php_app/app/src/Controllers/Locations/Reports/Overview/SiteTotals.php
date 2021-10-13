<?php


namespace App\Controllers\Locations\Reports\Overview;

use Exception;
use JsonSerializable;

/**
 * Class SiteTotals
 * @package App\Controllers\Locations\Reports\Overview
 */
final class SiteTotals implements JsonSerializable
{
    /**
     * @var string $serial
     */
    private $serial;

    /**
     * @var Totals $totals
     */
    private $totals;

    /**
     * @param array $data
     * @return static
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Totals::fromArray($data),
            $data['serial'] ?? ''
        );
    }

    /**
     * SiteTotals constructor.
     * @param string $serial
     * @param Totals $totals
     */
    public function __construct(
        Totals $totals = null,
        string $serial = ""
    ) {
        $this->serial = $serial;
        $this->totals = $totals;
    }

    /**
     * @param SiteTotals $additionalTotals
     * @return $this
     * @throws Exception
     */
    public function withAdditionalSiteTotals(SiteTotals $additionalTotals): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalTotals($additionalTotals->getTotals());
        return $totals;
    }

    /**
     * @param int $returningUsers
     * @return $this
     */
    public function withAdditionalReturningUsers(int $returningUsers): self
    {
        $totals         = clone $this;
        $totals->totals = $this
            ->totals
            ->withAdditionalReturningUsers($returningUsers);
        return $totals;
    }

    /**
     * @param int $newUsers
     * @return $this
     */
    public function withAdditionalNewUsers(int $newUsers): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalNewUsers($newUsers);
        return $totals;
    }

    /**
     * @param int $dwellTime
     * @return $this
     * @throws Exception
     */
    public function withAdditionalDwellTime(int $dwellTime): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalDwellTime($dwellTime);
        return $totals;
    }

    /**
     * @param int $splashImpressions
     * @return $this
     */
    public function withAdditionalSplashImpressions(int $splashImpressions): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalSplashImpressions($splashImpressions);
        return $totals;
    }

    /**
     * @param int $connections
     * @return $this
     */
    public function withAdditionalConnections(int $connections): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalConnections($connections);
        return $totals;
    }

    /**
     * @param ReviewTotals|null $reviewTotals
     * @return $this
     */
    public function withAdditionalReviewsTotals(?ReviewTotals $reviewTotals): self
    {
        $totals         = clone $this;
        $totals->totals = $this->totals->withAdditionalReviewsTotals($reviewTotals);
        return $totals;
    }

    /**
     * @return Totals
     */
    public function getTotals(): Totals
    {
        return $this->totals;
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        return $this->serial;
    }

    /**
     * @return int
     */
    public function getReturningUsers(): int
    {
        return $this->totals->getReturningUsers();
    }

    /**
     * @return int
     */
    public function getNewUsers(): int
    {
        return $this->totals->getNewUsers();
    }

    /**
     * @return int
     */
    public function getTotalUsers(): int
    {
        return $this->totals->getTotalUsers();
    }

    /**
     * @return int
     */
    public function getDwellTime(): int
    {
        return $this->totals->getDwellTime();
    }

    /**
     * @return int
     */
    public function getAverageDwellTime(): int
    {
        return $this->totals->getAverageDwellTime();
    }

    /**
     * @return int
     */
    public function getSplashImpressions(): int
    {
        return $this->totals->getSplashImpressions();
    }

    /**
     * @return int
     */
    public function getConnections(): int
    {
        return $this->totals->getConnections();
    }

    /**
     * @return ReviewTotals|null
     */
    public function getReviewsTotals(): ?ReviewTotals
    {
        return $this->totals->getReviewTotals();
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return array_merge(
            ['serial' => $this->getSerial()],
            $this->getTotals()->jsonSerialize()
        );
    }
}