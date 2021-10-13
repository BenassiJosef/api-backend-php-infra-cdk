<?php


namespace App\Package\Profile;


use App\Models\UserProfile;
use App\Package\Auth\ProfileSource;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Slim\Http\Request;

class UserProfileProvider
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * UserProfileProvider constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @return UserProfile
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function userProfileFromRequest(Request $request): UserProfile
    {
        /** @var ProfileSource | null $profileSource */
        $profileSource = $request->getAttribute(ProfileSource::class);

        if ($profileSource !== null) {
            return $profileSource->getProfile();
        }

        /** @var int $profileId */
        $profileId = $request->getAttribute('profileId');

        /** @var UserProfile $userProfile */
        $userProfile = $this
            ->entityManager
            ->find(UserProfile::class, $profileId);

        return $userProfile;
    }
}