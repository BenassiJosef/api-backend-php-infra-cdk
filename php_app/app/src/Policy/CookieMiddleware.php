<?php

namespace App\Policy;

use App\Package\WebTracking\WebCookies;
use Slim\Http\Request;
use Slim\Http\Response;

class CookieMiddleware
{


    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return mixed
     */

    public function __invoke(Request $request, Response $response, $next)
    {
        $webCookies = new WebCookies();
        $request    = $webCookies->handleMiddlewareRequest($request);
        $response    = $webCookies->handleMiddlewareResponse($response);

        return $next($request, $response);
    }


}