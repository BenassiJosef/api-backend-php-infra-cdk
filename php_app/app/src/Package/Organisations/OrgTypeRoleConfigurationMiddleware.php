<?php


namespace App\Package\Organisations;


use Slim\Http\Request;
use Slim\Http\Response;

class OrgTypeRoleConfigurationMiddleware
{
    const OrgTypeRoleKey = "orgTypeRolesKey";

    const OrgTypeKey = "orgTypeKey";

    /**
     * @var int[] $roles
     */
    private $roles = [];

    /**
     * @var string[] $orgTypes
     */
    private $orgTypes = [];

    /**
     * OrgTypeRoleConfigurationMiddleware constructor.
     * @param int[] $roles
     * @param string[] $orgTypes
     */
    public function __construct(array $roles, array $orgTypes)
    {
        $this->roles    = $roles;
        $this->orgTypes = $orgTypes;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        return $next(
            $request
                ->withAttribute(self::OrgTypeKey, $this->orgTypes)
                ->withAttribute(self::OrgTypeRoleKey, $this->roles),
            $response);
    }

}