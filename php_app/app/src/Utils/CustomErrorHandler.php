<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 18/09/2017
 * Time: 14:37
 */

namespace App\Utils;

class CustomErrorHandler
{
    public function __invoke($request, $response, $exception)
    {
        $http = Http::status(500);

        return $response->withJson($http, $http['status']);
    }
}
