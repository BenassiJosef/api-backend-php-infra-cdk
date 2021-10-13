<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 02/06/2017
 * Time: 14:58
 */

namespace App\Policy;
use Slim\Http\Request;
use Slim\Http\Response;

class isIgniteNet
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $userAgent  = $request->getHeader('User-Agent')[0];
        $isIgniteNet = stripos($userAgent, 'ignitenet-bb') !== false;

        if ($isIgniteNet === true) {

            $os      = substr($userAgent, strpos($userAgent, '/') + 1);
            $os      = substr($os, 0, strrpos($os, ' '));

            $request = $request->withAttribute('os', $os);
            $response =  $next($request, $response);

        } else {
            $response = $response->withStatus(400);
        }

        return $response;
    }
}