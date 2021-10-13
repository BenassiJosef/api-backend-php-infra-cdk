<?php


namespace App\Package\DataSources\Statements;

use App\Package\DataSources\Statement;


final class EmailUserRegistrationStatement implements Statement
{
    /**
     * @var string[] $emails
     */
    private $emails;

    /**
     * @var string[]
     */
    private $serials;

    /**
     * @var int $visits
     */
    private $visits;

    /**
     * EmailUserRegistrationStatement constructor.
     * @param string[] $emails
     * @param string[] $serials
     * @param int $visits
     */
    public function __construct(
        array $emails,
        array $serials,
        int $visits = 0
    ) {
        $this->emails  = $emails;
        $this->serials = $serials;
        $this->visits  = $visits;
    }

    public function statement(): string
    {
        $statement  = 'INSERT INTO `user_registrations` (serial, profile_id, number_of_visits, created_at, last_seen_at, email_opt_in_date, sms_opt_in_date, location_opt_in_date, migrated_to_org_reg_at) VALUES ';
        $emailKeys  = array_keys($this->keyedEmails());
        $serialKeys = array_keys($this->keyedSerials());
        $values     = [];
        foreach ($emailKeys as $emailKey) {
            foreach ($serialKeys as $serialKey) {
                $values[] = "(:$serialKey, (SELECT up.id FROM `core`.`user_profile` up WHERE up.email = :$emailKey), :visits, NOW(), NOW(), NOW(), NOW(), NOW(), NOW())";
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
            $this->keyedEmails()
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

    private function keyedEmails(): array
    {
        $keyed = [];
        foreach ($this->emails as $i => $email) {
            $keyed["email_$i"] = $email;
        }
        return $keyed;
    }
}