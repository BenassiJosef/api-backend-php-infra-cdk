<?php


namespace App\Package\Organisations\RBAC;


use App\Models\OauthUser;
use App\Package\Database\Statement;

/**
 * Class OrganizationIdsByUserAndSearch
 * @package App\Package\Organisations\RBAC
 */
class OrganizationIdsByUserAndSearch implements Statement
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
     * @return OrganizationIdsByUserAndSearch
     */
    public function setSearchTerm(?string $searchTerm): OrganizationIdsByUserAndSearch
    {
        $this->searchTerm = $searchTerm;
        return $this;
    }

    private function select(): string
    {
        if ($this->count) {
            return 'COUNT(DISTINCT c.id)';
        }
        return 'DISTINCT c.id';
    }

    /**
     * @inheritDoc
     */
    public function query(): string
    {
        $select = $this->select();

        $locationQuery =     "WITH RECURSIVE cte AS ((SELECT 
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
WHERE oa.user_id = :userId)
UNION ALL
SELECT 
	o.id,
    o.parent_organization_id
FROM organization o
INNER JOIN cte ON cte.id = o.parent_organization_id
)
SELECT ls.id
FROM cte
LEFT JOIN location_settings ls ON cte.id = ls.organization_id
WHERE ls.serial IS NOT NULL";

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
        ";
        if ($this->searchTerm !== null) {
            $query .= " INNER JOIN organization o ON o.id = c.id WHERE o.name LIKE :search ";
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
        }
        return $parameters;
    }
}
