<?php


namespace App\Package\Auth\Access\Config\Exceptions;

use App\Models\Role;
use App\Package\Exceptions\BaseException;
use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidRoleException
 * @package App\Package\Auth\Access\Exceptions
 */
class InvalidRoleException extends BaseException
{
    /**
     * InvalidRoleException constructor.
     * @param int $role
     * @throws Exception
     */
    public function __construct(int $role)
    {
        $validRoles = implode(', ', Role::$allRoles);
        parent::__construct(
            "Role with legacy id (${role}) does not exist, only (${validRoles}) exist.",
            StatusCode::HTTP_INTERNAL_SERVER_ERROR,
            [
                'validRoles' => Role::$allRoles,
            ]
        );
    }
}