<?php

namespace StampedeTests\app\src\Package\Member;

use App\Models\Role;
use App\Package\Member\UserCreationInput;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Throwable;

class UserCreationInputTest extends TestCase
{

    public function testCreateFromArraySucceeds()
    {
        $data = [
            'admin'                         => null,
            'reseller'                      => '675D83A2-7985-49FF-9EB1-36ABD62B900F',
            'email'                         => 'bob@example.com',
            'password'                      => 'hunter2',
            'company'                       => null,
            'organisationId'                => null,
            'parentOrganisationId'          => null,
            'first'                         => 'bob',
            'last'                          => 'bobertson',
            'role'                          => Role::LegacyAdmin,
            'country'                       => 'GB',
            'shouldCreateChargebeeCustomer' => true,
        ];
        try {
            $user = UserCreationInput::createFromArray($data);
            self::assertNotNull($user);
        } catch (Throwable $t) {
            self::fail($t->getMessage());
        }

    }
}
