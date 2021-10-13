<?php

/**
 * Created by chrisgreening on 27/02/2020 at 14:21
 * Copyright © 2020 Captive Ltd. All rights reserved.
 */

namespace StampedeTests\app\Policy;

use App\Controllers\Auth\_oAuth2Controller;
use App\Policy\Auth;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use StampedeTests\Helpers\DoctrineHelpers;

class DummyNext
{
    public function __invoke()
    {
    }
}

class AuthTest extends TestCase
{
    /**
     * @var _oAuth2Controller|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockAuth;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Request
     */
    private $request;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Response
     */
    private $response;
    /**
     * @var Auth
     */
    private $subject;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Route
     */
    private $route;
    /**
     * @var Auth
     */
    private $auth;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->mockAuth = $this->createMock(_oAuth2Controller::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->route = $this->createMock(Route::class);
        $this->auth = new Auth($this->mockAuth, $this->em);
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testInvokeWithInvalidToken()
    {
        $this->mockAuth->method("validateToken")->willReturn(false);
        $this->response->expects(self::once())->method("withStatus")->with(403, "Invalid token");
        $this->auth->__invoke($this->request, $this->response, function () {
        });
    }

    public function testInvokeWithMyStampede()
    {
        $this->mockAuth->method("validateToken")->willReturn(true);
        $this->mockAuth->method("getUid")->willReturn(123);
        $this->request->method("getParsedBody")->willReturn(["client_id" => "stampede.ai.my"]);
        $this->request->method("withAttribute")->will(
            $this->returnCallback(function (string $name, $value) {
                if ($name === 'user') {
                    self::assertTrue($value['valid']);
                    self::assertEquals($value["profileId"], 123);
                }
                return $this->request;
            })
        );

        //->willReturn($this->request);
        $this->request->method("getAttribute")->willReturnMap([
            ["route", null, $this->route],
            ["orgId", null, null]
        ]);

        $mockNext = $this->createMock(DummyNext::class);
        $mockNext->expects(self::once())->method("__invoke")->withAnyParameters();
        $this->auth->__invoke($this->request, $this->response, \Closure::fromCallable($mockNext));
    }

    public function testInvokeWithMe()
    {
        $me = ['uid' => 'profile123'];
        $this->mockAuth->method("validateToken")->willReturn(true);
        $this->mockAuth->method('currentUser')->willReturn($me);
        $this->route->method("getArguments")->willReturn(['uid' => 'me']);
        $this->request->method("withAttribute")->will(
            $this->returnCallback(function (string $name, $value) {
                if ($name === 'userId') {
                    self::assertEquals($value, 'profile123');
                }
                if ($name === 'user') {
                    self::assertEquals($value, ['uid' => 'profile123']);
                }
                return $this->request;
            })
        );

        $this->request->method("getAttribute")->willReturnMap([
            ["route", null, $this->route],
            ["orgId", null, null]
        ]);

        $mockNext = $this->createMock(DummyNext::class);
        $mockNext->expects(self::once())->method("__invoke")->withAnyParameters();
        $this->auth->__invoke($this->request, $this->response, \Closure::fromCallable($mockNext));
    }
}
