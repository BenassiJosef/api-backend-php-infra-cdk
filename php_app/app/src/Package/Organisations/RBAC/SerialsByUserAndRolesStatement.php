<?php


namespace App\Package\Organisations\RBAC;


use App\Models\OauthUser;
use App\Models\Role;
use App\Models\UserProfile;

class SerialsByUserAndRolesStatement implements \App\Package\Database\Statement
{
	/**
	 * @var OauthUser $oauthUser
	 */
	private $oauthUser;

	/**
	 * @var Role[] | null $roles
	 */
	private $roles;

	/**
	 * SerialsByUserAndRolesStatement constructor.
	 * @param OauthUser $oauthUser
	 * @param Role[] $roles
	 */
	public function __construct(
		OauthUser $oauthUser,
		?array $roles = []
	) {
		$this->oauthUser = $oauthUser;
		$this->roles     = array_values($roles);
	}


	private function whereClause(): string
	{
		$where = "WHERE oa.user_id = :userId";
		if ($this->roles === null || count($this->roles) === 0) {
			return $where;
		}
		$inStatement = from($this->roleParameters())
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
		return "${where} AND oa.role_id IN (${inStatement})";
	}

	/**
	 * @inheritDoc
	 */
	public function query(): string
	{
		$whereClause = $this->whereClause();
		return "WITH RECURSIVE cte AS ((SELECT 
    o.id, 
    o.parent_organization_id
FROM
    organization o
WHERE o.owner_id = :userId
UNION ALL
SELECT 
	o.id,
	o.parent_organization_id
FROM organization_access oa 
LEFT JOIN organization o ON o.id = oa.organization_id
${whereClause})
UNION ALL
SELECT 
	o.id,
    o.parent_organization_id
FROM organization o
INNER JOIN cte ON cte.id = o.parent_organization_id
)
SELECT ls.serial, ls.organization_id
FROM cte
LEFT JOIN location_settings ls ON cte.id = ls.organization_id
WHERE ls.serial IS NOT NULL";
	}

	/**
	 * @inheritDoc
	 */
	public function parameters(): array
	{
		return array_merge(
			[
				'userId' => $this->oauthUser->getUid(),
			],
			$this->roleParameters()
		);
	}

	private function roleParameters(): array
	{
		if (count($this->roles) === 0) {
			return [];
		}
		return from($this->roles)
			->select(
				function (Role $role): string {
					return $role->getId()->toString();
				},
				function (Role $role, int $i): string {
					return "role_${i}";
				}
			)
			->toArray();
	}
}
