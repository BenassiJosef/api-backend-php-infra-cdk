<?php


namespace App\Package\DataSources\Statements;


use App\Package\DataSources\Statement;
use App\Models\DataSources\Interaction;
use App\Package\DataSources\CandidateProfile;

final class EmailProfileStatement implements Statement
{

    /**
     * @param CandidateProfile[] $candidateProfiles
     * @return string[]
     */
    public static function emailsFromCandidateProfiles(array $candidateProfiles): array
    {
        $emails = [];
        foreach ($candidateProfiles as $candidateProfile) {
            $emails[] = $candidateProfile->getEmail();
        }
        return $emails;
    }

    /**
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @var string[] $emails
     */
    private $emails;

    /**
     * EmailProfileStatement constructor.
     * @param Interaction $interaction
     * @param string[] $emails
     */
    public function __construct(Interaction $interaction, array $emails)
    {
        $this->interaction = $interaction;
        $this->emails      = $emails;
    }

    public function statement(): string
    {
        $statement   = 'INSERT INTO `interaction_profile` (`id`, `interaction_id`, `profile_id`) VALUES ';
        $keyedEmails = $this->keyedEmails();
        $keys        = array_keys($keyedEmails);
        $values      = [];
        foreach ($keys as $key) {
            $values[] = "(UUID(), :interaction_id, (SELECT up.id FROM `user_profile` up WHERE up.email = :$key LIMIT 1))";
        }
        $statement .= implode(',', $values);
        $statement .= ';';
        return $statement;
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'interaction_id' => $this->interaction->getId()->toString(),
            ], $this->keyedEmails()
        );
    }

    private function keyedEmails(): array
    {
        $keyed = [];
        foreach ($this->emails as $i => $emails) {
            $keyed["emails_$i"] = $emails;
        }
        return $keyed;
    }
}