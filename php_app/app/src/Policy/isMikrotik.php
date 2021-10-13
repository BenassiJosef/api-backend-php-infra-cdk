<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 16/03/2017
 * Time: 12:11
 */

namespace App\Policy;

use Slim\Http\Request;
use Slim\Http\Response;


class isMikrotik
{

    /**
     *
     * EXPECTED EXAMPLE
     * Mikrotik/6.x Fetch
     *
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return mixed
     */

    public function __invoke(Request $request, Response $response, $next)
    {

        $userAgent  = $request->getHeader('User-Agent')[0];
        $isMikrotik = stripos($userAgent, 'Mikrotik') !== false;

        if ($isMikrotik === true) {

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