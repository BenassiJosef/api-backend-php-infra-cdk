<?php


namespace App\Package\DataSources\Hooks;


use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\UserProfile;

class StaticPayload implements Payload
{
    /**
     * @var DataSource $dataSource
     */
    private $dataSource;

    /**
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * Payload constructor.
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param UserProfile $userProfile
     */
    public function __construct(
        DataSource $dataSource,
        Interaction $interaction,
        UserProfile $userProfile
    ) {
        $this->dataSource  = $dataSource;
        $this->interaction = $interaction;
        $this->userProfile = $userProfile;
    }

    /**
     * @return DataSource
     */
    public function getDataSource(): DataSource
    {
        return $this->dataSource;
    }

    /**
     * @return Interaction
     */
    public function getInteraction(): Interaction
    {
        return $this->interaction;
    }

    /**
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile
    {
        return $this->userProfile;
    }
}