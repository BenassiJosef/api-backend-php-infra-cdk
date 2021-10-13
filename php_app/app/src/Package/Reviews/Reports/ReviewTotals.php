<?php

namespace App\Package\Reviews\Reports;

use App\Package\Reports\Time;
use JsonSerializable;

class ReviewTotals implements JsonSerializable
{

	/**
	 * @var int $reviews
	 */
	private $reviews;

	/**
	 * @var int $users
	 */
	private $users;

	/**
	 * @var int $oneStar
	 */
	private $oneStar;

	/**
	 * @var int $twoStar
	 */
	private $twoStar;

	/**
	 * @var int $threeStar
	 */
	private $threeStar;

	/**
	 * @var int $fourStar
	 */
	private $fourStar;

	/**
	 * @var int $fiveStar
	 */
	private $fiveStar;


	/**
	 * @var int $mixedSentiment
	 */
	private $mixedSentiment;
	/**
	 * @var int $neutralSentiment
	 */
	private $neutralSentiment;
	/**
	 * @var int $positiveSentiment
	 */
	private $positiveSentiment;
	/**
	 * @var int $negativeSentiment
	 */
	private $negativeSentiment;


	/**
	 * @var int $done
	 */
	private $done;
	/**
	 * @var int $stampede
	 */
	private $stampede;
	/**
	 * @var int $google
	 */
	private $google;
	/**
	 * @var int $tripadvisor
	 */
	private $tripadvisor;

	/**
	 * @var int $facebook
	 */
	private $facebook;

	/**
	 * @var Time $time
	 */
	private $time;


	/**
	 * Total constructor.

	 */
	public function __construct(
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
		int $stampede,
		?Time $time
	) {

		$this->users           = $users;
		$this->reviews           = $reviews;
		$this->oneStar           = $oneStar;
		$this->twoStar           = $twoStar;
		$this->threeStar           = $threeStar;
		$this->fourStar           = $fourStar;
		$this->fiveStar           = $fiveStar;
		$this->mixedSentiment  = $mixedSentiment;
		$this->negativeSentiment = $negativeSentiment;
		$this->neutralSentiment = $neutralSentiment;
		$this->positiveSentiment = $positiveSentiment;
		$this->done = $done;
		$this->google = $google;
		$this->facebook = $facebook;
		$this->tripadvisor = $tripadvisor;
		$this->stampede = $stampede;
		$this->time = $time;
	}

	public function updateTotal(
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
		$this->users           += $users;
		$this->reviews           += $reviews;
		$this->oneStar            += $oneStar;
		$this->twoStar           += $twoStar;
		$this->threeStar          += $threeStar;
		$this->fourStar           += $fourStar;
		$this->fiveStar           += $fiveStar;
		$this->mixedSentiment += $mixedSentiment;
		$this->negativeSentiment  += $negativeSentiment;
		$this->neutralSentiment += $neutralSentiment;
		$this->positiveSentiment += $positiveSentiment;
		$this->done += $done;
		$this->google += $google;
		$this->facebook += $facebook;
		$this->tripadvisor += $tripadvisor;
		$this->stampede += $stampede;
	}


	/**
	 * @return int
	 */
	public function getReturnUsers(): int
	{
		if (($this->reviews - $this->users) < 0) {
			return 0;
		}
		return $this->reviews - $this->users;
	}

	/**
	 * @return Time
	 */
	public function getTime(): Time
	{
		return $this->time;
	}

	public function meanRating(int $total): float
	{
		if ($total === 0) {
			return 0;
		}
		$sumOfReviewScores =
			(5 * $this->fiveStar) +
			(4 * $this->fourStar) +
			(3 * $this->threeStar) +
			(2 * $this->twoStar) +
			(1 * $this->oneStar);

		return round($sumOfReviewScores / $total, 1);
	}

	public function modeRating(): int
	{
		$arrayRatings = [
			$this->oneStar,
			$this->twoStar,
			$this->threeStar,
			$this->fourStar,
			$this->fiveStar
		];

		$values = array_count_values($arrayRatings);
		$mode =  array_search(max($values), $values);

		return $mode + 1;
	}

	public function totalSentiment()
	{
		return ($this->negativeSentiment +
			$this->positiveSentiment +
			$this->mixedSentiment +
			$this->neutralSentiment);
	}

	public function sentimentPercentage(int $sentiment)
	{
		if ($this->totalSentiment() <= 0) {
			return 0;
		}
		return round((($sentiment ?? 0) /  ($this->totalSentiment() ?? 0)) * 100, 2);
	}

	public function starPercentage(int $starRating)
	{
		if ($this->reviews <= 0) {
			return 0;
		}
		return round((($starRating ?? 0) /  ($this->reviews ?? 0)) * 100, 2);
	}


	public function netPromotorScore()
	{
		if ($this->reviews === 0) {
			return 0;
		}
		$totals =  ($this->fiveStar - ($this->oneStar + $this->twoStar + $this->threeStar));
		return round(($totals / $this->reviews) * 100, 2);
	}

	public function totalPlatforms()
	{
		return ($this->google +
			$this->facebook +
			$this->tripadvisor +
			$this->stampede);
	}

	public function platformPercentage(int $platform)
	{
		if ($this->totalPlatforms() <= 0) {
			return 0;
		}
		return round((($platform ?? 0) /  ($this->totalPlatforms() ?? 0)) * 100, 2);
	}

	public function percentageByReviews(int $count)
	{
		if ($this->reviews <= 0) {
			return 0;
		}

		return round((($count ?? 0) /  ($this->reviews ?? 0)) * 100, 2);
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerialize()
	{
		return [
			'users' => $this->users,
			'repeat_users' => $this->getReturnUsers(),
			'reviews' => $this->reviews,
			'nps_score' => $this->netPromotorScore(),
			'marked' => [
				'percentage' => [
					'done' => $this->percentageByReviews($this->done),
					'not_done' => $this->percentageByReviews($this->reviews - $this->done)
				],
				'count' => [
					'done' => $this->done,
					'not_done' => $this->reviews - $this->done
				]
			],
			'stars'                      => [
				'percentage' => [
					'one' => $this->starPercentage($this->oneStar),
					'two' => $this->starPercentage($this->twoStar),
					'three' => $this->starPercentage($this->threeStar),
					'four' => $this->starPercentage($this->fourStar),
					'five' => $this->starPercentage($this->fiveStar)
				],
				'averages' => [
					'review_mean' => $this->meanRating($this->reviews),
					'review_mode' => $this->modeRating(),
				],
				'count' => [
					'one' => $this->oneStar,
					'two' => $this->twoStar,
					'three' => $this->threeStar,
					'four' => $this->fourStar,
					'five' => $this->fiveStar
				]
			],
			'platform' => [

				'percentage' => [
					'google' =>  $this->platformPercentage($this->google),
					'facebook' =>  $this->platformPercentage($this->facebook),
					'tripadvisor' =>  $this->platformPercentage($this->tripadvisor),
					'stampede' =>  $this->platformPercentage($this->stampede)
				],
				'count' => [
					'google' => $this->google,
					'facebook' => $this->facebook,
					'tripadvisor' => $this->tripadvisor,
					'stampede' => $this->stampede
				]
			],
			'sentiment' => [
				'percentage' => [
					'negative' =>  $this->sentimentPercentage($this->negativeSentiment),
					'mixed' =>  $this->sentimentPercentage($this->mixedSentiment),
					'positive' =>  $this->sentimentPercentage($this->positiveSentiment),
					'neutral' =>  $this->sentimentPercentage($this->neutralSentiment)
				],
				'count' => [
					'negative' => $this->negativeSentiment,
					'positive' => $this->positiveSentiment,
					'mixed' => $this->mixedSentiment,
					'neutral' => $this->neutralSentiment
				]
			]
		];
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerializeChart()
	{
		return array_merge($this->jsonSerialize(), $this->getTime()->jsonSerialize());
	}
}
