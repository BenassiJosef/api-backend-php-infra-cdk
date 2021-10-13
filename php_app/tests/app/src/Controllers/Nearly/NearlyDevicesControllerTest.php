<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Nearly;

use App\Controllers\Nearly\_NearlyDevicesController;
use App\Controllers\User\UserOverviewController;
use App\Models\UserProfile;
use App\Package\Filtering\UserFilter;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class NearlyDevicesControllerTest extends TestCase
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

    public function testVerifyPayment(): void
    {
        $user = EntityHelpers::createUser($this->em, "bob@bob.com", "123","m");
        $payment = EntityHelpers::createPayment($this->em, $user, "serial1", "plan9FromOuterSpace");
        $logger = $this->createMock(Logger::class);
        $controller = new _NearlyDevicesController($logger, $this->em);
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([
            "email" => $user->email,
            "serial" => "serial1",
            "mac" => "mac123"
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method("withStatus")->with(200)->willReturn($response);
        $controller->verifyUserRoute($request, $response);
    }

    public function testVerifyNoPayments(): void
    {
        $user = EntityHelpers::createUser($this->em, "bob@bob.com", "123","m");

        $logger = $this->createMock(Logger::class);
        $controller = new _NearlyDevicesController($logger, $this->em);
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([
            "email" => $user->email,
            "serial" => "serial1",
            "mac" => "mac123"
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method("withStatus")->with(402)->willReturn($response);
        $controller->verifyUserRoute($request, $response);
    }

    public function testVerifyExpiredPayment():void
    {
        $user = EntityHelpers::createUser($this->em, "bob@bob.com", "123","m");
        $payment = EntityHelpers::createPayment($this->em, $user, "serial1", "plan9FromOuterSpace", 1, 200, 1, (new \DateTime())->sub(new \DateInterval('P1D')));
        $logger = $this->createMock(Logger::class);
        $controller = new _NearlyDevicesController($logger, $this->em);
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([
            "email" => $user->email,
            "serial" => "serial1",
            "mac" => "mac123"
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method("withStatus")->with(402)->willReturn($response);
        $controller->verifyUserRoute($request, $response);
    }

    public function testVerifyTooManyDevices():void
    {
        $user = EntityHelpers::createUser($this->em, "bob@bob.com", "123","m");
        $payment = EntityHelpers::createPayment($this->em, $user, "serial1", "plan9FromOuterSpace", 1, 200, 1);
        $device1 = EntityHelpers::createUserDevice($this->em, $user, "mac456", 'paid');
        $data1 = EntityHelpers::createUserData($this->em, $user, "serial1", $device1->mac, 'paid');
        $logger = $this->createMock(Logger::class);
        $controller = new _NearlyDevicesController($logger, $this->em);
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([
            "email" => $user->email,
            "serial" => "serial1",
            "mac" => "mac123"
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method("withStatus")->with(400)->willReturn($response);
        $controller->verifyUserRoute($request, $response);
    }
}