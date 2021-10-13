<?php

namespace App\Package\Auth\Tokens;

use Slim\Http\Request;

/**
 * Interface TokenSource
 * @package App\Package\Auth\Tokens
 */
interface TokenSource
{
    /**
     * @param Request $request
     * @return Token
     */
    public function token(Request $request): Token;
}
