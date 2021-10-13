<?php


namespace App\Package\DataSources\Statements;


use App\Models\DataSources\DataSource;
use App\Models\Organization;

class ProfileIdRegistrationSourceStatement implements \App\Package\DataSources\Statement
{
    /**
     * @var DataSource $dataSource
     */
    private $dataSource;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var int[] $profileIds
     */
    private $profileIds;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * EmailRegistrationSourceStatement constructor.
     * @param DataSource $dataSource
     * @param Organization $organization
     * @param int[] $profileIds
     * @param string[] $serials
     */
    public function __construct(
        DataSource $dataSource,
        Organization $organization,
        array $profileIds,
        array $serials
    ) {
        $this->dataSource   = $dataSource;
        $this->organization = $organization;
        $this->profileIds       = $profileIds;
        $this->serials      = $serials;
    }


    public function statement(): string
    {
        $statement    = 'INSERT INTO `registration_source` (`id`, `organization_registration_id`, `data_source_id`, `serial`, `interactions`, `last_interacted_at`, `created_at`) VALUES ';
        $keyedProfileIds  = $this->keyedProfileIds();
        $profileIdKeys    = array_keys($keyedProfileIds);
        $keyedSerials = $this->keyedSerials();
        $serialKeys   = array_keys($keyedSerials);
        $values       = [];
        foreach ($serialKeys as $serialKey) {
            foreach ($profileIdKeys as $profileIdKey) {
                $organizationLookup = $this->organizationRegistrationLookup($profileIdKey);
                $values[]           = "(UUID(), $organizationLookup, :data_source_id, :$serialKey, 1, NOW(), NOW())";
            }
        }
        $statement .= implode(', ', $values);
        $statement .= "ON DUPLICATE KEY UPDATE interactions = interactions + 1, last_interacted_at = NOW();";
        return $statement;
    }

    private function organizationRegistrationLookup(string $profileKey): string
    {
        return "(SELECT `or`.`id` FROM `organization_registration` `or` WHERE `or`.`organization_id` = :organization_id AND `or`.`profile_id` = :$profileKey LIMIT 1)";
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'organization_id' => $this->organization->getId()->toString(),
                'data_source_id'  => $this->dataSource->getId()->toString(),
            ],
            $this->keyedSerials(),
            $this->keyedProfileIds(),
        );
    }

    private function keyedProfileIds(): array
    {
        $keyed = [];
        foreach ($this->profileIds as $i => $profileId) {
            $keyed["profile_id_${i}"] = $profileId;
        }
        return $keyed;
    }

    private function keyedSerials(): array
    {
        $keyed = [];
        foreach ($this->serials as $i => $serial) {
            $keyed["serial_${i}"] = $serial;
        }
        return $keyed;
    }
}