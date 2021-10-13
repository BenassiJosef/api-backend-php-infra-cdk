<?php


namespace App\Package\DataSources;


use App\Models\UserProfile;

/**
 * Interface ProfileSaver
 * @package App\Package\DataSources
 */
interface ProfileSaver
{
    /**
     * @param CandidateProfile $profile
     * @param OptInStatuses|null $optInStatusesOverride
     * @return void
     */
    public function saveCandidateProfile(CandidateProfile $profile, OptInStatuses $optInStatusesOverride = null);

    /**
     * @param string $email
     * @param OptInStatuses|null $optInStatuses
     * @return void
     */
    public function saveEmail(string $email, OptInStatuses $optInStatuses = null);

    /**
     * @param int $profileId
     * @return void
     */
    public function saveProfileId(int $profileId);

    /**
     * @param UserProfile $userProfile
     * @return void
     */
    public function saveUserProfile(UserProfile $userProfile);
}