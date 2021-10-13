<?php

namespace App\Package\Organisations\Locations;

use App\Models\OauthUser;
use App\Package\Database\Statement;

/**
 * Class LocationIdsByUserAndSearch
 * @package App\Package\Organisations\RBAC
 */
class LocationIdsByUserAndSearch implements Statement
{
	/**
	 * @var OauthUser $user
	 */
	private $user;

	/**
	 * @var string | null
	 */
	private $searchTerm;

	/**
	 * @var bool $count
	 */
	private $count;

	/**
	 * OrganizationIdsByUserAndSearch constructor.
	 * @param OauthUser $user
	 * @param bool $count
	 */
	public function __construct(
		OauthUser $user,
		bool $count = false
	) {
		$this->user  = $user;
		$this->count = $count;
	}

	/**
	 * @param string|null $searchTerm
	 * @return LocationIdsByUserAndSearch
	 */
	public function setSearchTerm(?string $searchTerm): LocationIdsByUserAndSearch
	{
		$this->searchTerm = $searchTerm;
		return $this;
	}

	private function select(): string
	{
		if ($this->count) {
			return 'COUNT(DISTINCT ls.serial)';
		}
		return 'DISTINCT ls.serial';
	}

	/**
	 * @inheritDoc
	 */
	public function query(): string
	{

		$inStatement = from($this->serialParameters())
			->toKeys()
			->select(
				function (string $key): string {
					return ":${key}";
				}
			)
			->aggregate(
				function (string $aggregate, string $key): string {
					return "${aggregate}, ${key}";
				}
			);

		$select = $this->select();
		$query  = "
WITH RECURSIVE cte (id, parent_organization_id) as (
		SELECT 
			o.id,
			o.parent_organization_id
		FROM organization o
		LEFT JOIN organization_access oa  
			ON oa.organization_id = o.id
		WHERE o.owner_id = :userId OR oa.user_id = :userId
	UNION ALL 
		SELECT 
			child.id,
			child.parent_organization_id
		FROM organization child 
        INNER JOIN cte 
			ON child.parent_organization_id = cte.id
)
SELECT ${select} FROM cte c  
INNER JOIN location_settings ls ON ls.organization_id = c.id AND ls.serial IN (${inStatement})     
";
		if ($this->searchTerm !== null) {
			$query .= " WHERE ls.serial = :serial_search OR ls.alias LIKE :search ";
		}
		return $query;
	}

	/**
	 * @inheritDoc
	 */
	public function parameters(): array
	{

		$parameters = [
			'userId' => $this->user->getUserId(),

		];
		if ($this->searchTerm !== null) {
			$term                 = $this->searchTerm;
			$parameters['search'] = "%${term}%";
			$parameters['serial_search'] = "${term}";
		}
		return array_merge(
			$parameters,
			$this->serialParameters()
		);
	}

	private function serialParameters(): array
	{
		$access = $this->user->getAccess();
		return from($access)
			->select(
				function (string $serial): string {
					return $serial;
				},
				function (string $serial, int $i): string {
					return "serial_${i}";
				}
			)
			->toArray();
	}
}
