<?php

namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\AggregateRequestValidator;
use App\Package\Auth\RequestValidator;
use App\Package\Organisations\UserRoleChecker;

/**
 * Class UserRequestValidatorFactory
 * @package App\Package\Auth\Access
 */
class UserRequestValidatorFactory implements UserRequestValidatorSource
{
    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * UserRequestValidatorFactory constructor.
     * @param UserRoleChecker $userRoleChecker
     */
    public function __construct(UserRoleChecker $userRoleChecker)
    {
        $this->userRoleChecker = $userRoleChecker;
    }

    /**
     * @inheritDoc
     */
    public function requestValidator(OauthUser $oauthUser): RequestValidator
    {
        return AggregateRequestValidator::fromRequestValidators(
            new LocationRequestValidator($this->userRoleChecker, $oauthUser),
            new OrgRequestValidator($this->userRoleChecker, $oauthUser),
            new OrgRequestValidator($this->userRoleChecker, $oauthUser, 'resellerOrgId'),
            new OrgTypeRoleRequestValidator($this->userRoleChecker, $oauthUser),
            new UserRequestValidator($this->userRoleChecker, $oauthUser)
        );
    }
}