<?php


namespace App\Package\DataSources\Statements;

use App\Package\DataSources\Statement;


final class ProfileIdUserRegistrationStatement implements Statement
{
    /**
     * @var int[] $profileIds
     */
    private $profileIds;

    /**
     * @var string[]
     */
    private $serials;

    /**
     * @var int $visits
     */
    private $visits;

    /**
     * ProfileIdUserRegistrationStatement constructor.
     * @param int[] $profileIds
     * @param string[] $serials
     * @param int $visits
     */
    public function __construct(
        array $profileIds,
        array $serials,
        int $visits = 0
    ) {
        $this->profileIds = $profileIds;
        $this->serials    = $serials;
        $this->visits  = $visits;
    }

    public function statement(): string
    {
        $statement  = 'INSERT INTO `user_registrations` (serial, profile_id, number_of_visits, created_at, last_seen_at, email_opt_in_date, sms_opt_in_date, location_opt_in_date, migrated_to_org_reg_at) VALUES ';
        $profileKeys  = array_keys($this->keyedProfileIds());
        $serialKeys = array_keys($this->keyedSerials());
        $values     = [];
        foreach ($profileKeys as $profileKey) {
            foreach ($serialKeys as $serialKey) {
                $values[] = "(:$serialKey, :$profileKey, :visits, NOW(), NOW(), NOW(), NOW(), NOW(), NOW())";
            }
        }
        $statement .= implode(', ', $values);
        $statement .= " ON DUPLICATE KEY UPDATE number_of_visits = number_of_visits + :visits;";
        return $statement;
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'visits' => $this->visits,
            ],
            $this->keyedSerials(),
            $this->keyedProfileIds()
        );
    }

    private function keyedSerials(): array
    {
        $keyed = [];
        foreach ($this->serials as $i => $serial) {
            $keyed["serial_$i"] = $serial;
        }
        return $keyed;
    }

    private function keyedProfileIds(): array
    {
        $keyed = [];
        foreach ($this->profileIds as $i => $email) {
            $keyed["profile_$i"] = $email;
        }
        return $keyed;
    }
}