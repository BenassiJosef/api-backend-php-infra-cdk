<?php


namespace App\Package\Auth;


use App\Models\UserProfile;

interface ProfileSource
{
    /**
     * @return UserProfile
     */
    public function getProfile(): UserProfile;
}