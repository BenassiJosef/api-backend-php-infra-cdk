<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 20/02/2017
 * Time: 10:04
 */

namespace App\Controllers\ContentFiltering;

use App\Utils\Http;
use Curl\Curl;
use Slim\Http\Response;
use Slim\Http\Request;

class _ContentFilteringController
{
    private $curl = Curl::class;
    private $username = 'patrick@blackbx.io';
    private $password = 'Captive2458';
    private $baseUrl = 'https://www.safedns.com/nic/update';

    public function __construct()
    {
        $this->curl = new Curl();
        $this->curl->setBasicAuthentication($this->username, $this->password);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->enableFiltering('helloworld', '10.4.1.1');

        $response->withJson($send, $send['status']);
    }

    public function enableFiltering(string $serial, string $ip)
    {
        $this->curl->get($this->baseUrl, [
            'hostname' => $serial . '.product.stampede.ai',
            'myip'     => $ip
        ]);

        return Http::status($this->curl->httpStatusCode, $this->curl->response);
    }

    public function disableFiltering($serial)
    {
    }
}
