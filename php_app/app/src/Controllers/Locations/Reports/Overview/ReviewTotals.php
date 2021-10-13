<?php


namespace App\Controllers\Locations\Reports\Overview;

use JsonSerializable;

/**
 * Class ReviewTotals
 * @package App\Controllers\Locations\Reports\Overview
 */
final class ReviewTotals implements JsonSerializable
{
    /**
     * @var int $reviews
     */
    private $reviews;

    /**
     * @var int $stars
     */
    private $stars;

    /**
     * ReviewTotals constructor.
     * @param int $reviews
     * @param int $stars
     */
    public function __construct(int $reviews = 0, int $stars = 0)
    {
        $this->reviews = $reviews;
        $this->stars   = $stars;
    }

    /**
     * @param ReviewTotals $reviewTotals
     * @return $this
     */
    public function withAdditionalReviewsTotals(self $reviewTotals): self
    {
        return $this
            ->withAdditionalStars($reviewTotals->getStars())
            ->withAdditionalReviews($reviewTotals->getReviews());
    }

    /**
     * @param int $reviews
     * @return $this
     */
    public function withAdditionalReviews(int $reviews): self
    {
        $totals          = clone $this;
        $totals->reviews += $reviews;
        return $totals;
    }

    /**
     * @param int $stars
     * @return $this
     */
    public function withAdditionalStars(int $stars): self
    {
        $totals        = clone $this;
        $totals->stars += $stars;
        return $totals;
    }

    /**
     * @return int
     */
    public function getReviews(): int
    {
        return $this->reviews;
    }

    /**
     * @return int
     */
    public function getStars(): int
    {
        return $this->stars;
    }

    /**
     * @return float
     */
    public function getAverageStars(): float
    {
        if ($this->reviews === 0) {
            return 0;
        }
        return round($this->stars / $this->reviews, 2);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'reviews'      => $this->getReviews(),
            'stars'        => $this->getStars(),
            'averageStars' => $this->getAverageStars()
        ];
    }
}