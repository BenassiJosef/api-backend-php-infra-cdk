<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Integrations;

use App\Controllers\Integrations\UniFi\_UniFiController;
use App\Utils\Http;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class UnifiControllerTest extends TestCase
{
    private $em;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testGetUsersControllersRouteNoAccessUser(): void
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('withJson')
            ->with(Http::status(404, 'NO_CONTROLLERS_FOUND'), 404);
        $controller =  new _UniFiController($this->em);
        $controller->getUsersControllersRoute($request, $response);
    }

    public function testGetUsersControllersRouteWithAccessUser(): void
    {
        $request = $this->createMock(Request::class);
        $user = EntityHelpers::createOauthUser($this->em, "bob@banana.com", "password1", "", "");
        $org = EntityHelpers::createOrganisation($this->em, 'TestOrg', $user);
        $request->method('getAttribute')->willReturn($org->getId());
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('withJson')
            ->with(Http::status(404, 'NO_CONTROLLERS_FOUND'), 404);
        $controller =  new _UniFiController($this->em);
        $controller->getUsersControllersRoute($request, $response);
    }
}