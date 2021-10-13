<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/02/2017
 * Time: 13:44
 */

namespace App\Policy;


use App\Utils\Http;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Utils\WhiteListIpController;

class fromIP
{
    protected $ip;
    protected $listedIp;

    public function __construct()
    {
        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function __invoke(Request $request, Response $response, $next)
    {

        $ipController = new WhiteListIpController($this->ip);

        if ($ipController->verify()) {
            return $next($request, $response);
        }

        return $response->withStatus(406)->write(
            json_encode(Http::status(406, 'IP_IS_NOT_VALID'))
        );
    }
}