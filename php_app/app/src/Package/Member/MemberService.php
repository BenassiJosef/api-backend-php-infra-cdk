<?php


namespace App\Package\Member;


use App\Controllers\Auth\_PasswordController;
use App\Controllers\Members\MemberValidationController;
use App\Models\Notifications\NotificationType;
use App\Models\Notifications\UserNotificationLists;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Billing\ChargebeeCustomer;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\ORM\EntityManager;
use Exception;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use YaLinqo\Enumerable;

class InvalidEmailException extends InvalidArgumentException
{
}

class MemberNotFoundException extends Exception
{
};

class MemberService
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var EmailValidator $emailValidator
     */
    private $emailValidator;

    /**
     * @var _PasswordController $passwordController
     */
    private $passwordController;

    /**
     * @var OrganizationService
     */
    private $organisationService;

    /**
     * MemberService constructor.
     * @param EntityManager $entityManager
     * @param EmailValidator $emailValidator
     * @param _PasswordController $passwordController
     * @param OrganizationService $organisationService
     */
    public function __construct(
        EntityManager $entityManager,
        EmailValidator $emailValidator,
        _PasswordController $passwordController,
        OrganizationService $organisationService
    ) {
        $this->entityManager       = $entityManager;
        $this->emailValidator      = $emailValidator;
        $this->passwordController  = $passwordController;
        $this->organisationService = $organisationService;
    }

    /**
     * @param UserCreationInput $input
     * @return OauthUser
     * @throws \Doctrine\ORM\ORMException
     */
    public function createUser(UserCreationInput $input): OauthUser
    {
        if (!$this->emailValidator->validateEmail($input->getEmail())) {
            throw new InvalidEmailException();
        }
        $password = $this->createPassword($input);
        $user     = new OauthUser(
            $input->getEmail(),
            $password,
            $input->getCompany(),
            $input->getReseller(),
            $input->getFirst(),
            $input->getLast()
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->handleEmptyPassword($input, $user);
        $this->signupForDefaultUserNotifications($user);
        return $user;
    }

    /**
     * @param string $email
     * @return OauthUser|null
     */
    public function getUserByEmail(string $email): ?OauthUser
    {
        /** @var OauthUser | null $user */
        $user = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->findOneBy(
                [
                    'email' => $email,
                ]
            );
        return $user;
    }

    /**
     * @param string $id
     * @return OauthUser|null
     */
    public function getUserByIdString(string $id): ?OauthUser
    {
        /** @var OauthUser | null $user */
        $user = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->find($id);
        return $user;
    }

    /**
     * @param string $id
     * @param UserCreationInput $input
     * @return OauthUser
     * @throws MemberNotFoundException
     */
    public function updateUser(string $id, UserCreationInput $input): OauthUser
    {
        if ($input->getEmail() !== null && !$this->emailValidator->validateEmail($input->getEmail())) {
            throw new InvalidEmailException();
        }
        /** @var OauthUser $user */
        $user = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->find($id);
        if ($user === null) {
            throw new MemberNotFoundException();
        }
        $input->updateUser($user);
        $this->updateChargebee($user);
    }


    private function updateChargebee(OauthUser $user)
    {
        $orgs = $this->organisationService->getOrganizationsForOwnerWithBilling($user);
        if (count($orgs) === 0) {
            return;
        }
        $chargeBeeIds       = Enumerable::from($orgs)
            ->select(
                function (Organization $o) {
                    return $o->getChargebeeCustomerId();
                }
            )
            ->distinct()
            ->toArray();
        $chargeBeeCustomers = new ChargebeeCustomer($user);
        $udpateData         = $chargeBeeCustomers->toChargeBeeCustomerForUpdate();
        foreach ($chargeBeeIds as $chargeBeeId) {
            $this->chargeBeeAPI->updateCustomer($chargeBeeId, $udpateData);
        }
    }

    private function createPassword(UserCreationInput $input): string
    {
        if ($input->getPassword() === null) {
            return Uuid::uuid1();
        }
        return $input->getPassword();
    }

    private function handleEmptyPassword(UserCreationInput $input, OauthUser $user)
    {
        if ($input->getPassword() !== null) {
            return;
        }
        $this
            ->passwordController
            ->forgotPassword($user->getEmail(), $user->getUid(), "connect");
    }

    private function signupForDefaultUserNotifications(OauthUser $user)
    {
        // create the default notifications for the new user
        $weeklyReportsConnect               = new NotificationType($user->getUid(), 'connect', 'insight_weekly');
        $weeklyReportsEmail                 = new NotificationType($user->getUid(), 'email', 'insight_weekly');
        $weeklyReportsEmail->additionalInfo = $user->getEmail();
        $this->entityManager->persist($weeklyReportsEmail);
        $this->entityManager->persist($weeklyReportsConnect);
        // create the notification lists
        $newNotiList = new UserNotificationLists($user->getUid());
        $this->entityManager->persist($newNotiList);
    }


    public function search(int $offset, int $limit, string $search = null)
    {

        $query = "SELECT * FROM oauth_users ou ";

        if (!empty($search)) {
            $query .= "WHERE MATCH(ou.email, ou.company, ou.first, ou.last) AGAINST(:search IN NATURAL LANGUAGE MODE) ";
            $query .= "ORDER BY MATCH(ou.email, ou.company, ou.first, ou.last) AGAINST(:search IN NATURAL LANGUAGE MODE) DESC ";
        }

        if ($limit > 0) {
            $query .= "LIMIT :lmt ";
        }

        if ($offset > 0) {
            $query .= "OFFSET :offst ";
        }
        $query .= ";";

        $prepared = $this->entityManager->getConnection()->prepare($query);

        if (!empty($search)) {
            $searchParam = $search;
            $prepared->bindParam("search", $searchParam);
        }
        $limitParam = $limit;
        $offsetParam = $offset;
        if ($limit > 0) {
            $prepared->bindParam("lmt", $limitParam, \PDO::PARAM_INT);
        }

        if ($offset > 0) {
            $prepared->bindParam("offst", $offsetParam,  \PDO::PARAM_INT);
        }
        $prepared->execute();
        return $prepared->fetchAll();
    }
}
