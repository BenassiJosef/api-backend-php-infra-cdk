<?php

namespace App\Package\Auth\Access\Profile;

use App\Models\UserProfile;
use App\Package\Auth\RequestValidator;

/**
 * Interface ProfileRequestValidatorSource
 * @package App\Package\Auth\Access\Profile
 */
interface ProfileRequestValidatorSource
{
    /**
     * @param UserProfile $profile
     * @return RequestValidator
     */
    public function requestValidator(UserProfile $profile): RequestValidator;
}