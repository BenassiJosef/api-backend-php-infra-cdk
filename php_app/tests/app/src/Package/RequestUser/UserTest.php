<?php

namespace StampedeTests\app\src\Package\RequestUser;

use App\Package\RequestUser\User;
use Throwable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{

    public function testCreateFromArray()
    {
        $jsonString = "{\"uid\":\"fc34eaf5-4a01-4c29-be45-0d112847a21c\", \"email\":\"patrick-stampede-quote@blackbx.io\",\"role\":2,\"company\":\"STMPD\",\"first\":\"Patrick\",\"last\":\"Clover\",\"reseller\":null,\"password\":\"password\"}";
        $array      = json_decode($jsonString, true);

        try {
            $user          = User::createFromArray($array);
            $expectedArray = [
                'uid'         => "fc34eaf5-4a01-4c29-be45-0d112847a21c",
                'admin'       => null,
                'reseller'    => null,
                'email'       => "patrick-stampede-quote@blackbx.io",
                'company'     => "STMPD",
                'isChargeBee' => true,
                'first'       => "Patrick",
                'last'        => "Clover",
                'role'        => 2,
                'created'     => $user->getCreated(),
                'edited'      => $user->getEdited(),
                'access'      => [],
            ];
            $gotArray = $user->jsonSerialize();
            foreach ($expectedArray as $key => $value){
                self::assertEquals($value, $gotArray[$key]);
            }

        } catch (Throwable $t) {
            self::fail($t->getMessage());
        }
    }
}
