<?php

namespace App\Package\Auth\Access\Config;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ProfileWhitelistMiddleware
 * @package App\Package\Auth\Access\Config
 */
class ProfileWhitelistMiddleware
{
    /**
     * @param Request $request
     * @return bool
     */
    public static function isWhitelisted(Request $request): bool
    {
        return $request->getAttribute(ProfileWhitelistMiddleware::class, false);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        return $next(
            $request->withAttribute(
                ProfileWhitelistMiddleware::class,
                true
            ),
            $response
        );
    }
}