<?php

namespace App\Package\Reports;

use JsonSerializable;

class OrganisationReportsRow implements JsonSerializable
{

	/**
	 * @var OrganisationRegistrationTotal $totals
	 */
	private $totals;

	/**
	 * @var OrganisationRegistrationTotal[] $chartTotals
	 */
	private $chartTotals;

	public function __construct()
	{
		$this->chartTotals = [];
		$this->totals = new OrganisationRegistrationTotal(0, 0, 0, 0, 0, 0, 0, null);
	}

	public function updateTotal(
		string $key,
		Time $time,
		int $newUsers,
		int $users,
		int $activeUsers,
		int $optInUser,
		int $optInEmailUser,
		int $optInSmsUser,
		int $emailVerified
	) {
		$this->totals->updateTotal(
			$newUsers,
			$users,
			$activeUsers,
			$optInUser,
			$optInEmailUser,
			$optInSmsUser,
			$emailVerified
		);
		if (array_key_exists($key, $this->chartTotals)) {
			$this->chartTotals[$key]->updateTotal(
				$newUsers,
				$users,
				$activeUsers,
				$optInUser,
				$optInEmailUser,
				$optInSmsUser,
				$emailVerified
			);
		} else {
			$this->chartTotals[$key] = new OrganisationRegistrationTotal(
				$newUsers,
				$users,
				$activeUsers,
				$optInUser,
				$optInEmailUser,
				$optInSmsUser,
				$emailVerified,
				$time
			);
		}
	}



	/**
	 * @return OrganisationRegistrationTotal[]
	 */
	public function getChartTotals(): array
	{
		$flatTotals = [];
		foreach ($this->chartTotals as $total) {
			$flatTotals[] = $total->jsonSerializeChart();
		}
		return $flatTotals;
	}


	public function jsonSerialize()
	{
		return array_merge([
			'chart' => $this->getChartTotals()
		], $this->totals->jsonSerialize());
	}
}
