<?php

namespace StampedeTests\app\src\Package\Auth;

use App\Models\OauthAccessTokens;

/**
 * Class DummyTokenSource
 * @package StampedeTests\app\src\Package\Auth
 */
class DummyTokenSource implements \App\Package\Auth\Tokens\AccessTokenSource
{
    /**
     * @var OauthAccessTokens[] $tokens
     */
    private $tokens;

    /**
     * DummyTokenSource constructor.
     * @param OauthAccessTokens[] $tokens
     */
    public function __construct(OauthAccessTokens ...$tokens)
    {
        $this->tokens = from($tokens)
            ->select(
                function (OauthAccessTokens $token): OauthAccessTokens {
                    return $token;
                },
                function (OauthAccessTokens $token): string {
                    return $token->getAccessToken();
                }
            )
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function token(string $token): ?OauthAccessTokens
    {
        if (!array_key_exists($token, $this->tokens)) {
            return null;
        }
        return $this->tokens[$token];
    }
}