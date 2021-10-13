<?php


namespace App\Package\DataSources;


use App\Models\DataSources\DataSource;
use App\Models\Organization;

class InteractionRequest
{
    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var DataSource $dataSource
     */
    private $dataSource;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * @var int $visits
     */
    private $visits;

    /**
     * InteractionRequest constructor.
     * @param Organization $organization
     * @param DataSource $dataSource
     * @param string[] $serials
     * @param int $visits
     */
    public function __construct(
        Organization $organization,
        DataSource $dataSource,
        array $serials,
        int $visits = 0
    ) {
        $this->organization = $organization;
        $this->dataSource   = $dataSource;
        $this->serials      = $serials;
        $this->visits       = $visits;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @return DataSource
     */
    public function getDataSource(): DataSource
    {
        return $this->dataSource;
    }

    /**
     * @return string[]
     */
    public function getSerials(): array
    {
        return $this->serials;
    }

    /**
     * @return int
     */
    public function getVisits(): int
    {
        return $this->visits;
    }
}