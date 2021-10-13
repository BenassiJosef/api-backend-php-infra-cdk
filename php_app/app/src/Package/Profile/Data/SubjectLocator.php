<?php


namespace App\Package\Profile\Data;


use App\Models\UserProfile;
use App\Package\Profile\Data\Exceptions\SubjectNotFoundException;
use Doctrine\ORM\EntityManager;

/**
 * Class SubjectLocator
 * @package App\Package\Profile\Data
 */
class SubjectLocator
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * SubjectLocator constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager  = $entityManager;
    }

    /**
     * @param string $email
     * @return Subject
     * @throws SubjectNotFoundException
     */
    public function byEmail(string $email): Subject
    {
        return $this->fetchSubject('email', $email);
    }

    /**
     * @param int $id
     * @return Subject
     * @throws SubjectNotFoundException
     */
    public function byId(int $id): Subject
    {
        return $this->fetchSubject('id', $id);
    }

    /**
     * @param string $key
     * @param $value
     * @return Subject
     * @throws SubjectNotFoundException
     */
    private function fetchSubject(string $key, $value): Subject
    {
        /** @var UserProfile | null $userProfile */
        $userProfile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    $key => $value,
                ]
            );
        if ($userProfile === null) {
            throw new SubjectNotFoundException($key, $value);
        }
        return new Subject(
            $userProfile
        );
    }
}