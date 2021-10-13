<?php


namespace App\Package\Organisations\RBAC;


use App\Models\OauthUser;
use App\Models\Role;
use App\Package\Database\Statement;

class OrganizationIdsByUserAndRolesStatement implements Statement
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
     * @var bool $count
     */
    private $count;

    /**
     * OrganizationIdsByUserAndRolesStatement constructor.
     * @param OauthUser $oauthUser
     * @param Role[]|null $roles
     */
    public function __construct(
        OauthUser $oauthUser,
        ?array $roles = [],
        bool $count = false
    ) {
        $this->oauthUser = $oauthUser;
        $this->roles     = array_values($roles);
        $this->count     = $count;
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

    private function select(): string
    {
        if (!$this->count) {
            return  'cte.id';
        }
        return 'count(DISTINCT cte.id)';
    }

    /**
     * @inheritDoc
     */
    public function query(): string
    {
        $whereClause = $this->whereClause();
        $select = $this->select();
        return "
WITH RECURSIVE cte AS ((SELECT 
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
SELECT ${select} 
FROM cte";
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
        $roles = $this->roles;
        return from($roles)
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