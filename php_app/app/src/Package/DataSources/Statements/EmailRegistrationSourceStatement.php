<?php


namespace App\Package\DataSources\Statements;


use App\Models\DataSources\DataSource;
use App\Models\Organization;

class EmailRegistrationSourceStatement implements \App\Package\DataSources\Statement
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
     * @var string[] $emails
     */
    private $emails;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * EmailRegistrationSourceStatement constructor.
     * @param DataSource $dataSource
     * @param Organization $organization
     * @param string[] $emails
     * @param string[] $serials
     */
    public function __construct(
        DataSource $dataSource,
        Organization $organization,
        array $emails,
        array $serials
    ) {
        $this->dataSource   = $dataSource;
        $this->organization = $organization;
        $this->emails       = $emails;
        $this->serials      = $serials;
    }


    public function statement(): string
    {
        $statement    = 'INSERT INTO `registration_source` (`id`, `organization_registration_id`, `data_source_id`, `serial`, `interactions`, `last_interacted_at`, `created_at`) VALUES ';
        $keyedEmails  = $this->keyedEmails();
        $emailKeys    = array_keys($keyedEmails);
        $keyedSerials = $this->keyedSerials();
        $serialKeys   = array_keys($keyedSerials);
        $values       = [];
        foreach ($serialKeys as $serialKey) {
            foreach ($emailKeys as $emailKey) {
                $organizationLookup = $this->organizationLookupFromEmailKey($emailKey);
                $values[]           = "(UUID(), $organizationLookup, :data_source_id, :$serialKey, 1, NOW(), NOW())";
            }
        }
        $statement .= implode(', ', $values);
        $statement .= "ON DUPLICATE KEY UPDATE interactions = interactions + 1, last_interacted_at = NOW();";
        return $statement;
    }

    private function organizationLookupFromEmailKey(string $emailKey): string
    {
        return $this->organizationRegistrationLookup($this->profileIdLookup($emailKey));
    }

    private function organizationRegistrationLookup(string $profileLookup): string
    {
        return "(SELECT `or`.`id` FROM `organization_registration` `or` WHERE `or`.`organization_id` = :organization_id AND `or`.`profile_id` = $profileLookup LIMIT 1)";
    }

    private function profileIdLookup(string $emailKey): string
    {
        return "(SELECT up.id FROM user_profile up WHERE up.email = :$emailKey LIMIT 1)";
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'organization_id' => $this->organization->getId()->toString(),
                'data_source_id'  => $this->dataSource->getId()->toString(),
            ],
            $this->keyedSerials(),
            $this->keyedEmails(),
        );
    }

    private function keyedEmails(): array
    {
        $keyed = [];
        foreach ($this->emails as $i => $email) {
            $keyed["email_${i}"] = $email;
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