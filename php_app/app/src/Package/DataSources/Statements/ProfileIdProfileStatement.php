<?php


namespace App\Package\DataSources\Statements;


use App\Package\DataSources\Statement;
use App\Models\DataSources\Interaction;
use App\Package\DataSources\CandidateProfile;

final class ProfileIdProfileStatement implements Statement
{

    /**
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @var int[] $profileIds
     */
    private $profileIds;

    /**
     * EmailProfileStatement constructor.
     * @param Interaction $interaction
     * @param int[] $profileIds
     */
    public function __construct(Interaction $interaction, array $profileIds)
    {
        $this->interaction = $interaction;
        $this->profileIds  = $profileIds;
    }

    public function statement(): string
    {
        $statement   = 'INSERT INTO `interaction_profile` (`id`, `interaction_id`, `profile_id`) VALUES ';
        $keyedProfiles = $this->keyedProfileIds();
        $keys        = array_keys($keyedProfiles);
        $values      = [];
        foreach ($keys as $key) {
            $values[] = "(UUID(), :interaction_id, :$key)";
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
            ], $this->keyedProfileIds()
        );
    }

    private function keyedProfileIds(): array
    {
        $keyed = [];
        foreach ($this->profileIds as $i => $profileId) {
            $keyed["profile_id_$i"] = $profileId;
        }
        return $keyed;
    }
}