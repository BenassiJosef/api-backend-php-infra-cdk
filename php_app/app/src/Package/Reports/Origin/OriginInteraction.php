<?php

namespace App\Package\Reports\Origin;

use DateTime;
use JsonSerializable;

class OriginInteraction implements JsonSerializable
{
	/**
	 * @var int $profileId
	 */
	private $profileId;

	/**
	 * @var float $lat
	 */
	private $lat;

	/**
	 * @var float $lng
	 */
	private $lng;

	/**
	 * @var string $gender
	 */
	private $gender;

	/**
	 * @var DateTime $lastInteractedAt
	 */
	private $lastInteractedAt;

	/**
	 * @var string $serial
	 */
	private $serial;


	public function __construct(
		int $profileId,
		float $lat,
		float $lng,
		DateTime $lastInteractedAt,
		$gender = 'unknown',
		string $serial = null
	) {
		$this->profileId = $profileId;
		$this->lat = $lat;
		$this->lng = $lng;
		$this->gender = $gender;
		$this->lastInteractedAt = $lastInteractedAt;
		$this->serial = $serial;
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerialize()
	{
		return [
			'profile_id'               => $this->profileId,
			'gender'                   => $this->gender,
			'lat'                      => $this->lat,
			'lng'                      => $this->lng,
			'last_interacted_at'       => $this->lastInteractedAt,
			'serial' 				   => $this->serial
		];
	}
}
