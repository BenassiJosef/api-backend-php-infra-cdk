<?php

namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\Access\Config\RoleConfig;
use App\Package\Auth\RequestValidator;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\DBAL\DBALException;
use Slim\Http\Request;
use Slim\Route;

/**
 * Class LocationRequestValidator
 * @package App\Package\Auth\Access
 */
class LocationRequestValidator implements RequestValidator
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
     * SerialRequestValidator constructor.
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
     * @throws DBALException
     */
    public function canRequest(string $service, Request $request): bool
    {
        $serial = AccessConfigurationMiddleware::argumentFromRequest(
            $request,
            'serial'
        );
        if ($serial === null) {
            return true;
        }
        $roleConfiguration = RoleConfig::fromRequest($request);
        if ($roleConfiguration === null) {
            return $this
                ->userRoleChecker
                ->hasAccessToLocationAsRole(
                    $this->user,
                    $serial
                );
        }
        return $this
            ->userRoleChecker
            ->hasAccessToLocationAsRole(
                $this->user,
                $serial,
                $roleConfiguration->getLegacyRoleIds()
            );
    }
}