<?php


namespace App\Controllers\User;

use App\Models\OauthUser;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Exception;

/**
 * Class MeRepository
 * @package App\Controllers\User
 */
class MeRepository
{
    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * MeRepository constructor.
     * @param UserRoleChecker $userRoleChecker
     */
    public function __construct(UserRoleChecker $userRoleChecker)
    {
        $this->userRoleChecker = $userRoleChecker;
    }

    /**
     * @param OauthUser $user
     * @return Me
     */
    public function me(OauthUser $user): Me
    {
        return new Me(
            Uuid::fromString($user->getUid()),
            $this->userRoleChecker->organisations($user),
            $this->userRoleChecker->locations($user),
            $this->userRoleChecker->organisationAccess($user)
        );
    }
}
