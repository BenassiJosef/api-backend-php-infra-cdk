<?php

namespace App\Package\Clients\InternalOAuth;

use App\Package\Clients\InternalOAuth\Exceptions\OAuthException;

/**
 * Interface TokenSource
 * @package App\Package\Clients\InternalOAuth
 */
interface TokenSource
{
    /**
     * @return Token
     * @throws OAuthException
     */
    public function token(): Token;
}