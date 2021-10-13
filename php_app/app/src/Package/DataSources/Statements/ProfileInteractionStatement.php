<?php


namespace App\Package\DataSources\Statements;

use App\Package\DataSources\Statement;
use App\Models\DataSources\Interaction;

final class ProfileInteractionStatement implements Statement
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
     * ProfileStatement constructor.
     * @param Interaction $interaction
     * @param int[] $profileIds
     */
    public function __construct(Interaction $interaction, array $profileIds)
    {
        $this->interaction = $interaction;
        $this->profileIds  = $profileIds;
    }

    /**
     * @return string
     */
    public function statement(): string
    {
        $statement     = 'INSERT INTO `interaction_profile` (`id`, `interaction_id`, `profile_id`) VALUES ';
        $keyedProfiles = $this->keyedProfiles();
        $keys          = array_keys($keyedProfiles);
        $values        = [];
        foreach ($keys as $key) {
            $values[] = "(UUID(), :interaction_id, :$key)";
        }
        $statement .= implode(',', $values);
        $statement .= ';';
        return $statement;
    }

    /**
     * @return array
     */
    public function arguments(): array
    {
        return array_merge(
            [
                'interaction_id' => $this->interaction->getId()->toString(),
            ],
            $this->keyedProfiles(),
        );
    }

    /**
     * @return array
     */
    private function keyedProfiles(): array
    {
        $keyedProfiles = [];
        foreach ($this->profileIds as $i => $profileId) {
            $keyedProfiles["profile_$i"] = $profileId;
        }
        return $keyedProfiles;
    }
}