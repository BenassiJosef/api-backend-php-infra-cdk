<?php


namespace App\Package\DataSources\Statements;


use App\Models\Organization;
use App\Package\DataSources\Statement;

class ProfileIdOrganizationRegistrationStatement implements Statement
{
    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var int[] $profileIds
     */
    private $profileIds;

    /**
     * EmailOrganizationRegistrationStatement constructor.
     * @param Organization $organization
     * @param int[] $profileIds
     */
    public function __construct(Organization $organization, array $profileIds)
    {
        $this->organization = $organization;
        $this->profileIds   = $profileIds;
    }

    public function statement(): string
    {
        $statement   = 'INSERT INTO `organization_registration` (`id`, `organization_id`, `profile_id`, `last_interacted_at`, `created_at`) VALUES ';
        $keyedProfileIds = $this->keyedProfileIds();
        $keys        = array_keys($keyedProfileIds);
        $values      = [];
        foreach ($keys as $key) {
            $values[] = "(UUID(), :organizationId, :$key, NOW(), NOW())";
        }
        $statement .= implode(', ', $values);
        $statement .= 'ON DUPLICATE KEY UPDATE last_interacted_at = NOW();';
        return $statement;
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'organizationId' => $this->organization->getId(),
            ],
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
}