<?php


namespace App\Package\DataSources;


use App\Models\UserProfile;
use App\Package\Async\Flusher;
use App\Package\Async\FlushException;

class FlushingProfileSaver implements Flusher, ProfileSaver
{
    /**
     * @var ProfileInteraction $profileInteraction
     */
    private $profileInteraction;

    /**
     * @var CandidateProfile[] $candidateProfiles
     */
    private $candidateProfiles = [];

    /**
     * @var int[] $profileIds
     */
    private $profileIds = [];

    /**
     * FlushingProfileSaver constructor.
     * @param ProfileInteraction $profileInteraction
     */
    public function __construct(
        ProfileInteraction $profileInteraction
    ) {
        $this->profileInteraction = $profileInteraction;
    }


    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        if (count($this->profileIds) > 0) {
            $this->flushProfileIds();
        }
        if (count($this->candidateProfiles) > 0) {
            $this->flushCandidateProfiles();
        }
    }

    private function flushProfileIds()
    {
        $profileIds = $this->profileIds;
        $this->profileInteraction->saveProfileIds($profileIds);
        $this->profileIds = [];
    }

    private function flushCandidateProfiles()
    {
        $profiles = $this->candidateProfiles;
        $this->profileInteraction->saveCandidateProfiles($profiles);
        $this->candidateProfiles = [];
    }

    /**
     * @inheritDoc
     */
    public function saveCandidateProfile(CandidateProfile $profile, OptInStatuses $optInStatusesOverride = null)
    {
        if ($optInStatusesOverride !== null) {
            $profile->setOptInStatuses($optInStatusesOverride);
        }
        $this->candidateProfiles[] = $profile;
    }

    /**
     * @inheritDoc
     */
    public function saveEmail(string $email, OptInStatuses $optInStatuses = null)
    {
        $profile = new CandidateProfile($email);
        $profile->setOptInStatuses($optInStatuses);
        $this->candidateProfiles[] = $profile;
    }

    /**
     * @inheritDoc
     */
    public function saveProfileId(int $profileId)
    {
        $this->profileIds[] = $profileId;
    }

    /**
     * @inheritDoc
     */
    public function saveUserProfile(UserProfile $userProfile)
    {
        $this->profileIds[] = $userProfile->getId();
    }

}