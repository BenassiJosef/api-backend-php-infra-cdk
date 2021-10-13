<?php


namespace App\Package\Organisations\Children;


use App\Models\Organization;
use App\Package\Database\Statement;

/**
 * Class OrganizationChildrenStatement
 * @package App\Package\Organisations\Children
 */
class OrganizationChildrenStatement implements Statement
{

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var bool $count
     */
    private $count;

    /**
     * @var string | null
     */
    private $searchTerm;

    /**
     * OrganizationChildrenStatement constructor.
     * @param Organization $organization
     * @param bool $count
     */
    public function __construct(
        Organization $organization,
        bool $count = false
    ) {
        $this->organization = $organization;
        $this->count        = $count;
    }

    /**
     * @param string|null $searchTerm
     * @return OrganizationChildrenStatement
     */
    public function setSearchTerm(?string $searchTerm): OrganizationChildrenStatement
    {
        $this->searchTerm = $searchTerm;
        return $this;
    }

    private function select(): string
    {
        if ($this->count) {
            return "COUNT(DISTINCT c.id)";
        }
        return "DISTINCT c.id";
    }

    /**
     * @return string
     */
    public function query(): string
    {
        $select= $this->select();
        $query = "WITH RECURSIVE cte AS (
	SELECT o.id, o.parent_organization_id
    FROM organization o
    WHERE o.id = :organizationId
    UNION ALL
    SELECT o.id, o.parent_organization_id
    FROM organization o
    INNER JOIN cte c ON c.id = o.parent_organization_id
) SELECT ${select} FROM cte c";
        if ($this->searchTerm !== null) {
            $query .= " INNER JOIN organization o ON o.id = c.id WHERE o.name LIKE :term";
        }
        return $query;
    }

    /**
     * @return array
     */
    public function parameters(): array
    {
        $parameters = [
            'organizationId' => $this->organization->getId()->toString(),
        ];
        if ($this->searchTerm !== null) {
            $term = $this->searchTerm;
            $parameters['term'] = "%${term}%";
        }
        return $parameters;
    }
}