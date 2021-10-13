<?php


namespace App\Package\DataSources\Hooks;


use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\UserProfile;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\OptInStatuses;
use App\Package\DataSources\ProfileSaver;
use Doctrine\ORM\EntityManager;
use Exception;

class HookNotifier
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Hook[] $hooks
     */
    private $hooks = [];

    /**
     * HookNotifier constructor.
     * @param EntityManager $entityManager
     * @param Hook[] $hooks
     */
    public function __construct(
        EntityManager $entityManager,
        array $hooks = []
    ) {
        $this->entityManager = $entityManager;
        $this->hooks         = $hooks;
    }

    public function register(Hook $hook): void
    {
        $this->hooks[] = $hook;
    }

    /**
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param CandidateProfile $profile
     * @throws Exception
     */
    public function saveCandidateProfile(
        DataSource $dataSource,
        Interaction $interaction,
        CandidateProfile $profile
    ): void {
        $this->saveEmail($dataSource, $interaction, $profile->getEmail());
    }

    /**
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param string $email
     * @throws Exception
     */
    public function saveEmail(
        DataSource $dataSource,
        Interaction $interaction,
        string $email
    ): void {
        $entityManager = $this->entityManager;
        $this->triggerHooks(
            new LazyPayload(
                $dataSource,
                $interaction,
                function () use ($entityManager, $email): UserProfile {
                    /** @var UserProfile | null $userProfile */
                    $userProfile = $entityManager
                        ->getRepository(UserProfile::class)
                        ->findOneBy(
                            [
                                'email' => $email,
                            ]
                        );
                    if ($userProfile === null) {
                        throw new Exception('could not find user profile');
                    }
                    return $userProfile;
                }
            )
        );
    }

    /**
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param int $profileId
     * @throws Exception
     */
    public function saveProfileId(
        DataSource $dataSource,
        Interaction $interaction,
        int $profileId
    ): void {
        $entityManager = $this->entityManager;
        $this->triggerHooks(
            new LazyPayload(
                $dataSource,
                $interaction,
                function () use ($entityManager, $profileId): UserProfile {
                    /** @var UserProfile | null $userProfile */
                    $userProfile = $entityManager
                        ->getRepository(UserProfile::class)
                        ->find($profileId);
                    if ($userProfile === null) {
                        throw new Exception('could not find user profile');
                    }
                    return $userProfile;
                }
            )
        );
    }

    /**
     * @param DataSource $dataSource
     * @param Interaction $interaction
     * @param UserProfile $userProfile
     */
    public function saveUserProfile(
        DataSource $dataSource,
        Interaction $interaction,
        UserProfile $userProfile
    ): void {
        $this->triggerHooks(new StaticPayload($dataSource, $interaction, $userProfile));
    }

    /**
     * @param Payload $payload
     */
    private function triggerHooks(Payload $payload): void
    {
        foreach ($this->hooks as $hook) {
            $hook->notify($payload);
        }
    }
}