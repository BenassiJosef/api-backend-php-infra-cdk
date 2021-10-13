<?php

namespace App\Package\Auth;

use Slim\Http\Request;

/**
 * Interface RequestValidator
 * @package App\Package\Auth
 */
interface RequestValidator
{
    /**
     * @param string $service
     * @param Request $request
     * @return bool
     */
    public function canRequest(string $service, Request $request): bool;
}
