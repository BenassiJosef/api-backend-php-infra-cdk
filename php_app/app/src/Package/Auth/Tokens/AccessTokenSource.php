<?php

namespace App\Package\Auth\Tokens;


use App\Models\OauthAccessTokens;

/**
 * Class AccessTokenRepository
 * @package App\Package\Auth\Tokens
 */
interface AccessTokenSource
{
    /**
     * @param string $token
     * @return OauthAccessTokens|null
     */
    public function token(string $token): ?OauthAccessTokens;
}