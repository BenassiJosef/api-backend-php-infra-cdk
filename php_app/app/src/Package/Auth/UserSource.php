<?php


namespace App\Package\Auth;


use App\Models\OauthUser;

/**
 * Interface UserProvider
 * @package App\Package\Auth
 */
interface UserSource
{
    /**
     * @return string
     */
    public function getUserId(): string;

    /**
     * @return OauthUser
     */
    public function getUser(): OauthUser;
}