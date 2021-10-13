<?php


namespace App\Package\DataSources;


use App\Models\UserProfile;
use JsonSerializable;

class SingleProfileResponse implements JsonSerializable
{
    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * SingleProfileResponse constructor.
     * @param UserProfile $userProfile
     * @param string[] $serials
     */
    public function __construct(UserProfile $userProfile, array $serials)
    {
        $this->userProfile = $userProfile;
        $this->serials     = $serials;
    }

    /**
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile
    {
        return $this->userProfile;
    }

    /**
     * @return string[]
     */
    public function getSerials(): array
    {
        return $this->serials;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'profile'    => $this->getUserProfile(),
            'newSerials' => $this->getSerials(),
        ];
    }
}