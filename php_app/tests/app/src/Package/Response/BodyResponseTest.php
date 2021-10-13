<?php

namespace StampedeTests\app\src\Package\Response;

use App\Models\Organization;
use App\Package\Loyalty\App\LoyaltyOrganization;
use App\Package\Response\BodyResponse;
use OAuth2\HttpFoundationBridge\Response;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class BodyResponseTest extends TestCase
{

    public function testJsonSerialize()
    {
        $organization = new LoyaltyOrganization(
            Uuid::fromString('9a9dbb9c-07e9-11eb-be05-acde48001122'),
            'My Organization'
        );

        $response = BodyResponse::fromStatusAndBody(
            Response::HTTP_BAD_REQUEST,
            'you cannot do this',
            $organization
        );

        $expectedJson = [
            'type'   => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title'  => 'Bad Request',
            'status' => 400,
            'detail' => 'you cannot do this',
            'body'   => [
                'id'   => '9a9dbb9c-07e9-11eb-be05-acde48001122',
                'name' => 'My Organization'
            ]
        ];
        $expected     = json_encode($expectedJson);
        $got          = json_encode($response->jsonSerialize());
        self::assertEquals($expected, $got);
    }
}
