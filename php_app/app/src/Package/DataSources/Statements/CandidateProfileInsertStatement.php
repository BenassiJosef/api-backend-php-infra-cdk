<?php


namespace App\Package\DataSources\Statements;

use App\Package\DataSources\OptInStatuses;
use App\Package\DataSources\Statement;
use App\Package\DataSources\CandidateProfile;

final class CandidateProfileInsertStatement implements Statement
{
    /**
     * @param array $emails
     * @param OptInStatuses|null $optInStatuses
     * @return array
     */
    public static function emailsToCandidateProfiles(array $emails, OptInStatuses $optInStatuses = null)
    {
        if ($optInStatuses === null) {
            $optInStatuses = new OptInStatuses(true, true, true);
        }
        $profiles = [];
        foreach ($emails as $email) {
            $candidateProfile = new CandidateProfile($email);
            $candidateProfile->setOptInStatuses($optInStatuses);
            $profiles[] = $candidateProfile;
        }
        return $profiles;
    }

    /**
     * @var CandidateProfile[] $candidateProfiles
     */
    private $candidateProfiles;

    /**
     * CandidateProfileInsertStatement constructor.
     * @param CandidateProfile[] $candidateProfiles
     */
    public function __construct(
        array $candidateProfiles
    ) {
        $this->candidateProfiles = $candidateProfiles;
    }


    public function statement(): string
    {
        $statement     = "INSERT INTO `user_profile` (`email`, `first`, `last`, `country`, `postcode`, `gender`, `phone`, `birth_month`, `birth_day`, `verified_id`, `timestamp`, `updated`) VALUES ";
        $keyedProfiles = $this->keyProfiles();
        $values        = [];
        foreach ($keyedProfiles as $keyedProfile) {
            $keys     = array_keys($keyedProfile);
            $coloned  = array_map(
                function ($key) {
                    return ":$key";
                }, $keys
            );
            $coloned  = array_merge($coloned, ['NOW()']);
            $joined   = implode(', ', $coloned);
            $values[] = "($joined)";
        }
        $statement .= implode(', ', $values);
        $statement .= ' ON DUPLICATE KEY UPDATE ';
        $statement .= 'first = COALESCE(VALUES(first), first), ';
        $statement .= 'last = COALESCE(VALUES(last), last), ';
        $statement .= 'phone = COALESCE(VALUES(phone), phone), ';
        $statement .= 'birth_month = COALESCE(VALUES(birth_month), birth_month), ';
        $statement .= 'birth_day = COALESCE(VALUES(birth_day), birth_day), ';
        $statement .= 'country = COALESCE(VALUES(country), country), ';
        $statement .= 'postcode = COALESCE(VALUES(postcode), postcode), ';
        $statement .= 'gender = COALESCE(VALUES(gender), gender), ';
        $statement .= 'updated = NOW(); ';
        return $statement;
    }

    public function arguments(): array
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
            $fields      = $candidateProfile->jsonSerialize();
            $keyedFields = [];
            foreach ($fields as $fieldName => $value) {
                $keyedFields["${fieldName}_${i}"] = $value;
            }
            $arguments[] = $keyedFields;
        }
        return $arguments;
    }
}