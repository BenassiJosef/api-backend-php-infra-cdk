<?php


namespace App\Package\DataSources\Statements;


use App\Models\Organization;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\Statement;
use DateTime;

class CandidateProfileOrganizationRegistrationStatement implements Statement
{
    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var CandidateProfile[] $candidateProfiles
     */
    private $candidateProfiles;

    /**
     * EmailOrganizationRegistrationStatement constructor.
     * @param Organization $organization
     * @param CandidateProfile[] $candidateProfiles
     */
    public function __construct(Organization $organization, array $candidateProfiles)
    {
        $this->organization      = $organization;
        $this->candidateProfiles = $candidateProfiles;
    }

    public function statement(): string
    {
        $statement     = 'INSERT INTO `organization_registration` (`id`, `organization_id`, `profile_id`, `last_interacted_at`, `created_at`, `data_opt_in_at`, `email_opt_in_at`, `sms_opt_in_at`) VALUES ';
        $keyedProfiles = $this->keyProfiles();
        $values        = [];
        foreach ($keyedProfiles as $keyedProfile) {
            $keys    = array_keys($keyedProfile);
            $coloned = array_map(
                function ($key) {
                    return ":$key";
                }, $keys
            );
            [$emailKey, $dataOptInKey, $emailOptInKey, $smsOptInKey] = $coloned;
            $values[] = "(UUID(), :organizationId, (SELECT up.id FROM `user_profile` up WHERE up.email = $emailKey LIMIT 1), NOW(), NOW(), $dataOptInKey, $emailOptInKey, $smsOptInKey)";
        }
        $statement .= implode(', ', $values);
        $statement .= ' ON DUPLICATE KEY UPDATE last_interacted_at = NOW(), ';
        $statement .= 'data_opt_in_at = CASE WHEN data_opt_in_at IS NULL THEN data_opt_in_at ELSE VALUES(data_opt_in_at) END, ';
        $statement .= 'email_opt_in_at = CASE WHEN email_opt_in_at IS NULL THEN email_opt_in_at ELSE VALUES(email_opt_in_at) END, ';
        $statement .= 'sms_opt_in_at = CASE WHEN sms_opt_in_at IS NULL THEN sms_opt_in_at ELSE VALUES(sms_opt_in_at) END; ';

        return $statement;
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'organizationId' => $this->organization->getId(),
            ],
            $this->flattenedArgs()
        );
    }

    private function flattenedArgs(): array
    {
        $arguments = [];
        foreach ($this->keyProfiles() as $keyProfile) {
            foreach ($keyProfile as $k => $v) {
                $arguments[$k] = $v;
            }
        }
        return $arguments;
    }

    private function keyProfiles(): array
    {
        $arguments = [];
        foreach ($this->candidateProfiles as $i => $candidateProfile) {
            $fields      = $candidateProfile->getOptInStatuses()->jsonSerialize();
            $keyedFields = [
                "email_${i}" => $candidateProfile->getEmail(),
            ];
            foreach ($fields as $fieldName => $value) {
                $keyedFields["${fieldName}_${i}"] = $value;
            }
            $arguments[] = $keyedFields;
        }
        return $arguments;
    }
}