<?php

namespace App\Package\Auth\Access\User;

use App\Models\OauthUser;
use App\Package\Auth\RequestValidator;

/**
 * Interface UserRequestValidatorSource
 * @package App\Package\Auth\Access
 */
interface UserRequestValidatorSource
{
    /**
     * @param OauthUser $oauthUser
     * @return RequestValidator
     */
    public function requestValidator(OauthUser $oauthUser): RequestValidator;
}