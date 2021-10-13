<?php

namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\RequestValidator;
use App\Package\Organisations\UserRoleChecker;
use Slim\Http\Request;
use Twilio\Rest\Autopilot\V1\Assistant\ReadQueryOptions;

/**
 * Class UserRequestValidator
 * @package App\Package\Auth\Access\User
 */
class UserRequestValidator implements RequestValidator
{

    /**
     * @param Request $request
     * @return bool
     */
    public static function isSelfRequest(Request $request): bool
    {
        $userId = self::userIdFromRequest($request);
        if ($userId === null) {
            return false;
        }
        return in_array($userId, self::$selfKeywords);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    public static function userIdFromRequest(Request $request): ?string
    {
        foreach (self::$attributeNames as $attributeName) {
            $userId = AccessConfigurationMiddleware::argumentFromRequest(
                $request,
                $attributeName
            );
            if ($userId !== null) {
                return $userId;
            }
        }
        return null;
    }

    private static $attributeNames = [
        'userId',
        'uid'
    ];

    private static $selfKeywords = [
        'self',
        'me'
    ];


    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * @var OauthUser $oauthUser
     */
    private $oauthUser;

    /**
     * UserRequestValidator constructor.
     * @param UserRoleChecker $userRoleChecker
     * @param OauthUser $oauthUser
     */
    public function __construct(
        UserRoleChecker $userRoleChecker,
        OauthUser $oauthUser
    ) {
        $this->userRoleChecker = $userRoleChecker;
        $this->oauthUser       = $oauthUser;
    }

    /**
     * @inheritDoc
     */
    public function canRequest(string $service, Request $request): bool
    {
        $userId = self::userIdFromRequest($request);
        if ($userId === null) {
            return true;
        }

        $tokenUserId = $this->oauthUser->getUid();
        if (self::isSelfRequest($request)) {
            $userId = $tokenUserId;
        }

        if ($userId !== $tokenUserId) {
            return $this
                ->userRoleChecker
                ->hasAdminAccessToUser($this->oauthUser, $userId);
        }

        return true;
    }
}