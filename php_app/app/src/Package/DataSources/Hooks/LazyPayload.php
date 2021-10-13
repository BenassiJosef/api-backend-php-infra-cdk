<?php


namespace App\Package\DataSources\Hooks;


use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\UserProfile;

class LazyPayload implements Payload
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
     * @var callable $userProfileClosure
     */
    private $userProfileClosure;

    /**
     * @var UserProfile | null $userProfile
     */
    private $userProfile;

    /**
     * LazyPayload constructor.
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param callable $userProfileClosure
     */
    public function __construct(
        DataSource $dataSource,
        Interaction $interaction,
        callable $userProfileClosure
    ) {
        $this->dataSource         = $dataSource;
        $this->interaction        = $interaction;
        $this->userProfileClosure = $userProfileClosure;
    }


    /**
     * @inheritDoc
     */
    public function getDataSource(): DataSource
    {
        return $this->dataSource;
    }

    /**
     * @inheritDoc
     */
    public function getInteraction(): Interaction
    {
        return $this->interaction;
    }

    /**
     * @inheritDoc
     */
    public function getUserProfile(): UserProfile
    {
        if ($this->userProfile === null) {
            $this->userProfile = ($this->userProfileClosure)();
        }
        return $this->userProfile;
    }
}