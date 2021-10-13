<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 17/05/2017
 * Time: 10:49
 */

namespace App\Utils;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;

class HttpFoundationFactorySubClass extends HttpFoundationFactory
{
    public function createRequest(ServerRequestInterface $psrRequest)
    {
        $parsedBody = $psrRequest->getParsedBody();
        $parsedBody = is_array($parsedBody) ? $parsedBody : [];

        $request = new Request(
            $psrRequest->getQueryParams(),
            $parsedBody,
            $psrRequest->getAttributes(),
            $psrRequest->getCookieParams(),
            $_FILES,
            $psrRequest->getServerParams(),
            $psrRequest->getBody()->__toString()
        );
        $request->headers->replace($psrRequest->getHeaders());

        return $request;
    }
}
