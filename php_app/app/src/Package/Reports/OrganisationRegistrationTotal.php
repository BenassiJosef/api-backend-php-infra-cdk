<?php

namespace App\Package\Reports;

use JsonSerializable;

class OrganisationRegistrationTotal implements JsonSerializable
{
	/**
	 * @var int $newUsers
	 */
	private $newUsers;

	/**
	 * @var int $users
	 */
	private $users;

	/**
	 * @var int $activeUsers
	 */
	private $activeUsers;

	/**
	 * @var int $optInUsers
	 */
	private $optInUsers;

	/**
	 * @var int $optInEmailUsers
	 */
	private $optInEmailUsers;

	/**
	 * @var int $optInSmsUsers
	 */
	private $optInSmsUsers;

	/**
	 * @var int $emailVerified
	 */
	private $emailVerified;

	/**
	 * @var Time $time
	 */
	private $time;


	/**
	 * Total constructor.

	 */
	public function __construct(
		int $newUsers,
		int $users,
		int $activeUsers,
		int $optInUsers,
		int $optInEmailUsers,
		int $optInSmsUsers,
		int $emailVerified,
		?Time $time
	) {
		$this->newUsers           = $newUsers;
		$this->users           = $users;
		$this->activeUsers           = $activeUsers;
		$this->optInUsers           = $optInUsers;
		$this->optInEmailUsers           = $optInEmailUsers;
		$this->optInSmsUsers           = $optInSmsUsers;
		$this->emailVerified           = $emailVerified;
		$this->time = $time;
	}

	public function updateTotal(
		int $newUsers,
		int $users,
		int $activeUsers,
		int $optInUsers,
		int $optInEmailUsers,
		int $optInSmsUsers,
		int $emailVerified
	) {
		$this->newUsers += $newUsers;
		$this->users += $users;
		$this->activeUsers += $activeUsers;
		$this->optInSmsUsers += $optInSmsUsers;
		$this->optInUsers += $optInUsers;
		$this->optInEmailUsers += $optInEmailUsers;
		$this->emailVerified += $emailVerified;
	}


	public function getReturnUsers(): int
	{
		if ($this->users - $this->newUsers < 0) {
			return 0;
		}
		return $this->users - $this->newUsers;
	}

	/**
	 * @return Time
	 */
	public function getTime(): Time
	{
		return $this->time;
	}

	public function getPercentageOf(int $argument)
	{
		if ($this->users === 0) return 0;
		if ($argument === 0) return 0;
		return round(($argument / $this->users) * 100, 2);
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerialize()
	{
		return [
			'totals' => [
				'new_users'                      => $this->newUsers,
				'users'                      => $this->users,
				'active_users'                      => $this->activeUsers,
				'return_users'                      => $this->getReturnUsers(),
				'opt_in_users'                      => $this->optInUsers,
				'opt_in_email_users'                      => $this->optInEmailUsers,
				'opt_in_sms_users'                      => $this->optInSmsUsers,
				'email_verified' => $this->emailVerified
			],
			'percents' => [
				'new_users' => $this->getPercentageOf($this->newUsers),
				'return_users' => $this->getPercentageOf($this->getReturnUsers()),
				'opt_in_users' => $this->getPercentageOf($this->optInUsers),
				'opt_in_email_users'                      => $this->getPercentageOf($this->optInEmailUsers),
				'opt_in_sms_users'                      => $this->getPercentageOf($this->optInSmsUsers),
				'email_verified' => $this->getPercentageOf($this->emailVerified)
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
