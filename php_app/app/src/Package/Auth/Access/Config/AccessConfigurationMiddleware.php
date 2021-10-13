<?php

namespace App\Package\Auth\Access\Config;

use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Route;

/**
 * Class AccessConfigurationMiddleware
 * @package App\Package\Auth\Access
 */
class AccessConfigurationMiddleware
{
    /**
     * @param Request $request
     * @param string $key
     * @return string|null
     */
    public static function argumentFromRequest(Request $request, string $key): ?string
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');
        if ($route === null) {
            return null;
        }
        return $route->getArgument($key);
    }

    /**
     * @var RoleConfig | OrgTypeRoleConfig $configuration
     */
    private $configuration;

    /**
     * AccessConfigurationMiddleware constructor.
     * @param OrgTypeRoleConfig|RoleConfig $configuration
     */
    public function __construct(RoleConfig $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        return $next(
            $request
                ->withAttribute(
                    get_class($this->configuration),
                    $this->configuration
                ),
            $response
        );
    }
}