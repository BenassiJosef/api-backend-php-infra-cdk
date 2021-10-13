<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Marketing;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Marketing\_MarketingLegacy;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class MarketingLegacyTest extends TestCase
{
    private $em;
    private $logger;
    private $queueSender;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();
        $this->logger = $this->createMock(Logger::class);
        $this->queueSender = $this->createMock(QueueSender::class);
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    public function testSMSOptOutNotStopOrStart()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn([
            'Text' => "I'm a lumberjack"
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(400, 'NEITHER_A_STOP_OR_SEND'), 400);
        $controller->optOutSMSRoute($request, $response);
    }

    public function testSMSStopNoMarketingEvent()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller     = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn([
            'Text' => "STP1234",
            'From' => '123'
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(404), 404);
        $controller->optOutSMSRoute($request, $response);
    }

    public function testSMSStopPreview()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller     = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $me             = EntityHelpers::createMarketingEvent($this->em, 0, 'serial1', 'campaign1');
        $me->optOutCode = 'STP1234';
        $me->eventto    = '123';
        $this->em->persist($me);
        $this->em->flush($me);

        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn([
            'Text' => "STP1234",
            'From' => '123'
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(202, 'PREVIEWS_CAN_NOT_BE_CANCELLED'), 202);

        $controller->optOutSMSRoute($request, $response);
    }

    public function testSMSOptOut()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller     = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $up = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $ur = EntityHelpers::createUserRegistration($this->em, 'serial1', $up->id);
        $me             = EntityHelpers::createMarketingEvent($this->em, $up->id, 'serial1', 'campaign1');
        $me->optOutCode = 'STP1234';
        $me->eventto    = '123';
        $this->em->persist($me);
        $this->em->flush($me);

        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn([
            'Text' => "STP1234",
            'From' => '123'
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(200), 200);

        $controller->optOutSMSRoute($request, $response);

        $marketingOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy(["serial" => 'serial1', "uid" => $up->id, "type" => "sms"]);
        $this->assertNotNull($marketingOptOut);
        $this->assertTrue($ur->getSMSOptOut());
    }

    public function testSMSOptIn()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller     = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $up = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");

        $ur = EntityHelpers::createUserRegistration($this->em, 'serial1', $up->id);
        $ur->setSmsOptInDate(null);
        $this->em->persist($ur);

        $me             = EntityHelpers::createMarketingEvent($this->em, $up->id, 'serial1', 'campaign1');
        $me->optOutCode = 'START1234';
        $me->eventto    = '123';
        $this->em->persist($me);
        $this->em->flush($me);

        $mo = EntityHelpers::createMarketingOpt($this->em, $up->id, 'serial1', 'sms', true);

        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn([
            'Text' => "START1234",
            'From' => '123'
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(200), 200);

        $controller->optOutSMSRoute($request, $response);

        $marketingOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy(["serial" => 'serial1', "uid" => $up->id, "type" => "sms"]);
        $this->assertNull($marketingOptOut);
        $this->assertFalse($ur->getSMSOptOut());
    }

    public function testEmailOptOut()
    {
        $marketingCache = $this->createMock(CacheEngine::class);
        $controller     = new _MarketingLegacy($this->logger, $this->em, $marketingCache, new UserRegistrationRepository($this->logger, $this->em), $this->queueSender);
        $up = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $ur = EntityHelpers::createUserRegistration($this->em, 'serial1', $up->id);

        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn([
            'serial' => 'serial1',
            'uid' => $up->id
        ]);
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with(Http::status(200), 200);

        $this->queueSender->expects($this->once())->method('sendMessage')->with([
            'profileId' => $up->id,
            'serial' => 'serial1'
        ]);
        $controller->optOutEmailRoute($request, $response);

        $marketingOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy(["serial" => 'serial1', "uid" => $up->id, "type" => "email"]);
        $this->assertNotNull($marketingOptOut);
        $this->assertTrue($ur->getEmailOptOut());
    }
}
