<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/03/2017
 * Time: 16:35
 */

namespace App\Policy;

use App\Utils\Http;
use Slim\Http\Request;
use Slim\Http\Response;

class isLocal
{

    public function __invoke(Request $request, Response $response, $next)
    {
        $whitelist = [
            '127.0.0.1',
            '::1',
            'localhost',
            '62.31.137.188',
            '172.31.43.90'
        ];
        
        return $next($request, $response);
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return $next($request, $response);
        }
        $userAgent     = $request->getHeader('User-Agent')[0];
        $isCurl        = stripos($userAgent, 'curl') !== false;
        $isCronService = stripos($userAgent, 'cron-service') !== false;

        if ($isCurl === true || $isCronService === true) {
            return $next($request, $response);
        } else {
            return $response->withStatus(406)->withJson(Http::status(406,
                [
                    'ip'      => $_SERVER['REMOTE_ADDR'],
                    'address' => $_SERVER['SERVER_ADDR'],
                    'referer' => $_SERVER['HTTP_REFERER']
                ])
            );
        }
    }

}