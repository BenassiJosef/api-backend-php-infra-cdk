<?php

namespace App\Package\Auth\Access\Config;

use App\Models\Role;
use App\Package\Auth\Access\Config\Exceptions\InvalidRoleException;
use App\Package\Auth\AuthMiddleware;
use JsonSerializable;
use Slim\Http\Request;

/**
 * Class RoleConfiguration
 * @package App\Package\Auth\Access\Config
 */
class RoleConfig implements JsonSerializable
{
    /**
     * @param Request $request
     * @return static|null
     */
    public static function fromRequest(Request $request)
    {
        return $request->getAttribute(self::class);
    }

    /**
     * @return static
     * @throws InvalidRoleException
     */
    public static function all(): AccessConfigurationMiddleware
    {
        return new AccessConfigurationMiddleware(
            new self(
                ...Role::$allRoles
            )
        );
    }

    /**
     * @return static
     */
    public static function super(): AccessConfigurationMiddleware
    {
        return new AccessConfigurationMiddleware(
            new self(
                Role::LegacyAdmin,
                Role::LegacyReseller,
                Role::LegacySuperAdmin
            )
        );
    }

    /**
     * @param int ...$roles
     * @throws InvalidRoleException
     */
    public static function validateRoles(int ...$roles): void
    {
        foreach ($roles as $role) {
            if (!in_array($role, Role::$allRoles)) {
                throw new InvalidRoleException($role);
            }
        }
    }

    /**
     * @var bool[] $roles
     */
    private $roles;

    /**
     * ResourceRoleConfiguration constructor.
     * @param int ...$roles
     * @throws InvalidRoleException
     */
    public function __construct(int ...$roles)
    {
        self::validateRoles(...$roles);
        $this->roles = from($roles)
            ->select(
                function (int $role): bool {
                    return true;
                },
                function (int $role): int {
                    return $role;
                }
            )
            ->toArray();
    }

    /**
     * @return int[]
     */
    public function formattedRoles(): array
    {
        return from($this->roles)
            ->toKeys()
            ->select(
                function (int $key): int {
                    return $key;
                },
                function (int $key): string {
                    return Role::$stringRepresentation[$key];
                }
            )
            ->toArray();
    }

    /**
     * @return int[]
     */
    public function getLegacyRoleIds(): array
    {
        return array_keys($this->roles);
    }

    /**
     * @param int $role
     * @return bool
     * @throws InvalidRoleException
     */
    public function hasRole(int $role): bool
    {
        self::validateRoles($role);
        return array_key_exists($role, $this->roles);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'allowedRoles' => $this->formattedRoles(),
        ];
    }
}