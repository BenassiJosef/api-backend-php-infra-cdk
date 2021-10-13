<?php

namespace App\Package\Auth\Tokens;

use App\Package\Auth\Scopes\Scopes;
use DateTime;
use App\Package\Auth\RequestValidator;

/**
 * Interface Token
 * @package App\Package\Auth
 */
interface Token extends RequestValidator
{
    /**
     * @return string
     */
    public function getToken(): string;

    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @return Scopes
     */
    public function getScopes(): Scopes;

    /**
     * @return DateTime
     */
    public function getExpiresAt(): DateTime;
}
