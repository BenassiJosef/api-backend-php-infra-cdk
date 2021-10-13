<?php


namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\Access\Config\RoleConfig;
use App\Package\Auth\RequestValidator;
use App\Package\Organisations\UserRoleChecker;
use Slim\Http\Request;
use Slim\Route;

/**
 * Class OrganizationRequestValidator
 * @package App\Package\Auth\Access\User
 */
class OrgRequestValidator implements RequestValidator
{
    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * @var OauthUser $oauthUser
     */
    private $oauthUser;

    /**
     * @var string $organizationIdKey
     */
    private $organizationIdKey;

    /**
     * OrganizationRequestValidator constructor.
     * @param UserRoleChecker $userRoleChecker
     * @param OauthUser $oauthUser
     * @param string $organizationIdKey
     */
    public function __construct(
        UserRoleChecker $userRoleChecker,
        OauthUser $oauthUser,
        string $organizationIdKey = 'orgId'
    ) {
        $this->userRoleChecker   = $userRoleChecker;
        $this->oauthUser         = $oauthUser;
        $this->organizationIdKey = $organizationIdKey;
    }

    /**
     * @inheritDoc
     */
    public function canRequest(string $service, Request $request): bool
    {
        $organizationId = AccessConfigurationMiddleware::argumentFromRequest(
            $request,
            $this->organizationIdKey
        );
        if ($organizationId === null) {
            return true;
        }
        $roleConfiguration = RoleConfig::fromRequest($request);
        if ($roleConfiguration === null) {
            return $this
                ->userRoleChecker
                ->hasAccessToOrganizationAsRole(
                    $this->oauthUser,
                    $organizationId
                );
        }
        return $this
            ->userRoleChecker
            ->hasAccessToOrganizationAsRole(
                $this->oauthUser,
                $organizationId,
                $roleConfiguration->getLegacyRoleIds()
            );
    }
}