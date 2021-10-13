<?php

namespace App\Package\Auth\Access\Config;

use App\Models\Organization;
use App\Models\Role;
use App\Package\Auth\Access\Config\Exceptions\InvalidOrganizationTypeException;
use Slim\Http\Request;

/**
 * Class OrganizationTypeAndRoleConfiguration
 * @package App\Package\Auth\Access
 */
class OrgTypeRoleConfig extends RoleConfig
{
    /**
     * @return AccessConfigurationMiddleware
     * @throws Exceptions\InvalidRoleException
     */
    public static function superRoot(): AccessConfigurationMiddleware
    {
        return new AccessConfigurationMiddleware(
            new self(
                [
                    Role::LegacyAdmin,
                    Role::LegacySuperAdmin,
                    Role::LegacyReseller,
                ],
                Organization::RootType
            )
        );
    }

    /**
     * @param Request $request
     * @return static|null
     */
    public static function fromRequest(Request $request)
    {
        return $request->getAttribute(self::class);
    }

    /**
     * @param string ...$organizationTypes
     * @throws InvalidOrganizationTypeException
     */
    public static function validateOrganizationTypes(string ...$organizationTypes): void
    {
        foreach ($organizationTypes as $organizationType) {
            if (!in_array($organizationType, Organization::$allTypes)) {
                throw new InvalidOrganizationTypeException($organizationType);
            }
        }
    }

    /**
     * @var bool[] $organizationTypes
     */
    private $organizationTypes;

    /**
     * OrganizationTypeAndRoleConfiguration constructor.
     * @param array $roles
     * @param string ...$organizationTypes
     * @throws Exceptions\InvalidRoleException
     */
    public function __construct(array $roles, string ...$organizationTypes)
    {
        parent::__construct(...$roles);
        $this->organizationTypes = from($organizationTypes)
            ->select(
                function (string $organizationType): bool {
                    self::validateOrganizationTypes($organizationType);
                    return true;
                },
                function (string $organizationType): string {
                    return $organizationType;
                }
            )
            ->toArray();
    }

    /**
     * @return string[]
     */
    public function getOrganizationTypes(): array
    {
        return array_keys($this->organizationTypes);
    }
}
