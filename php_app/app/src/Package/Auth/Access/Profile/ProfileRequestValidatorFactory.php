<?php


namespace App\Package\Auth\Access\Profile;


use App\Models\UserProfile;
use App\Package\Auth\RequestValidator;

class ProfileRequestValidatorFactory implements ProfileRequestValidatorSource
{
    /**
     * @param UserProfile $profile
     * @return RequestValidator
     */
    public function requestValidator(UserProfile $profile): RequestValidator
    {
        return new ProfileRequestValidator($profile);
    }

}