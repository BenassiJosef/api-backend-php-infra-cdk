<?php

namespace App\Package\Auth\ExternalServices;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UriInterface;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Route;

/**
 * Class RequestFactory
 * @package App\Package\Auth\ExternalServices
 */
class RequestAdapter
{
    /**
     * @return Environment
     */
    private static function environment(): Environment
    {
        return Environment::mock(
            [
                'determineRouteBeforeAppMiddleware' => false,
                'displayErrorDetails'               => false,
                'addContentLengthHeader'            => false,
            ]
        );
    }

    /**
     * @param AccessCheckRequest $request
     * @return Request
     */
    public function adapt(AccessCheckRequest $request): Request
    {
        $route       = $this->route($request);
        $slimRequest = $this->baseRequest($request);
        if ($route !== null) {
            $slimRequest = $slimRequest->withAttribute('route', $route);
        }
        return $slimRequest;
    }

    /**
     * @param AccessCheckRequest $request
     * @return Route|null
     */
    private function route(AccessCheckRequest $request): ?Route
    {
        $pattern = $request->getPattern();
        if ($pattern === null) {
            return null;
        }
        $route = new Route(
            $request->getMethod(),
            $pattern,
            function (Request $request, Response $response): Response {
                return $response->withJson(['hi :)']);
            }
        );
        return $route->setArguments($request->getParameters());
    }

    /**
     * @param AccessCheckRequest $request
     * @return Request
     */
    private function baseRequest(AccessCheckRequest $request): Request
    {
        return new Request(
            $request->getMethod(),
            $this->uri($request),
            Headers::createFromEnvironment(self::environment()),
            [],
            [],
            Utils::streamFor(null)
        );
    }

    /**
     * @param AccessCheckRequest $request
     * @return UriInterface
     */
    private function uri(AccessCheckRequest $request): UriInterface
    {
        return new Uri(
            'http',
            $request->getService(),
            null,
            $request->getPath(),
        );
    }
}