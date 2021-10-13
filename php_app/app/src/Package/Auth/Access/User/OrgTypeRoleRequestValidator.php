<?php

namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\Access\Config\OrgTypeRoleConfig;
use App\Package\Auth\RequestValidator;
use App\Package\Organisations\UserRoleChecker;
use Slim\Http\Request;

/**
 * Class OrganizationTypeRoleRequestValidator
 * @package App\Package\Auth\Access
 */
class OrgTypeRoleRequestValidator implements RequestValidator
{
    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * @var OauthUser $user
     */
    private $user;

    /**
     * OrganizationTypeRoleRequestValidator constructor.
     * @param UserRoleChecker $userRoleChecker
     * @param OauthUser $user
     */
    public function __construct(
        UserRoleChecker $userRoleChecker,
        OauthUser $user
    ) {
        $this->userRoleChecker = $userRoleChecker;
        $this->user            = $user;
    }

    /**
     * @inheritDoc
     */
    public function canRequest(string $service, Request $request): bool
    {
        $organizationTypeRoleConfig = OrgTypeRoleConfig::fromRequest($request);
        if ($organizationTypeRoleConfig === null) {
            return true;
        }
        return $this
            ->userRoleChecker
            ->hasAccessToOrganizationType(
                $this->user,
                $organizationTypeRoleConfig->getOrganizationTypes(),
                $organizationTypeRoleConfig->getLegacyRoleIds()
            );
    }
}