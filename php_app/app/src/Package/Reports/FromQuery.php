<?php

namespace App\Package\Reports;

use DateInterval;
use DateTime;
use Slim\Http\Request;

class FromQuery
{


	/**
	 * @var string $organizationId
	 */
	private $organizationId;

	/**
	 * @var int $limit
	 */
	private $limit;


	/**
	 * @var int $offset
	 */
	private $offset;

	/**
	 * @var int $dataSourceId
	 */
	private $dataSourceId;

	/**
	 * @var bool $registrations
	 */
	private $registrations;

	/**
	 * @var string $serial
	 */
	private $serial;

	/**
	 * @var string $sort
	 */
	private $sort;

	/**
	 * @var DateTime $startDate
	 */
	private $startDate;

	/**
	 * @var DateTime $endDate
	 */
	private $endDate;

	public function __construct(
		Request $request
	) {
		$now       = new DateTime('now');
		$this->organizationId = $request->getAttribute('orgId');
		$this->limit = (int) $request->getQueryParam('limit', 25);
		$this->offset = (int) $request->getQueryParam('offset', 0);
		$this->dataSourceId = $request->getQueryParam('data_source', null);
		$this->registrations = $request->getQueryParam('registrations', null) === 'true' ? true : false;
		$this->serial = $request->getQueryParam('serial', null);
		$this->startDate   = (int) $request->getParam('startDate') ??
			$now->sub(new DateInterval('P30D'))->getTimestamp();
		$this->endDate = (int) $request->getParam('endDate') ?? $now->getTimestamp();
		$this->sort = strtoupper($request->getQueryParam('sort', 'DESC'));
	}

	public function getOrganizationId(): string
	{
		return $this->organizationId;
	}

	public function getLimit(): int
	{
		return $this->limit;
	}

	public function getOffset(): int
	{
		return $this->offset;
	}

	public function getDataSourceId(): ?string
	{
		return $this->dataSourceId;
	}

	public function getOrganisationId(): ?string
	{
		return $this->organizationId;
	}

	public function getRegistrations(): bool
	{
		return $this->registrations;
	}

	public function getSerial(): ?string
	{
		return $this->serial;
	}

	public function getSort(): ?string
	{
		return $this->sort;
	}

	public function getStartDate(): DateTime
	{
		return (new DateTime())->setTimestamp($this->startDate);
	}

	public function getEndDate(): DateTime
	{
		return (new DateTime())->setTimestamp($this->endDate);
	}
}
