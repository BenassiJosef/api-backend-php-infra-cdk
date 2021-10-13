<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Nearly;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Nearly\NearlyProfile\NearlyOptOut;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Utils\Http;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;

final class NearlyOptOutTest extends TestCase
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

    public function testOptOutNoSerial() {
        $userRegistration = $this->createMock(UserRegistrationRepository::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $subject = new NearlyOptOut($this->logger, $this->em, $userRegistration, $this->queueSender);
        $request->method('getParsedBody')->willReturn([
        ]);
        $request->method('getAttribute')->with('nearlyUser')->willReturn(["profileId"=>"profile1"]);

        $response->expects($this->once())->method('withJson')->with(Http::status(400, 'NO_SERIAL'), 400);
        $subject->optOutRoute($request, $response);
    }

    public function testOptOutLocation() {
        $userRegistration = $this->createMock(UserRegistrationRepository::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $subject = new NearlyOptOut($this->logger, $this->em, $userRegistration, $this->queueSender);
        $request->method('getParsedBody')->willReturn([
            "data" => true,
            "serial" => "serial1"
        ]);
        $request->method('getAttribute')->with('nearlyUser')->willReturn(["profileId"=>"profile1"]);

        $response->expects($this->once())->method('withJson')->with(Http::status(200, 'OK'), 200);
        $userRegistration->expects($this->once())->method('updateLocationOptOut')->with("profile1", "serial1", true);
        $userRegistration->expects($this->once())->method('updateMarketingOptOuts')->with("profile1", "serial1", true, true);

        $subject->optOutRoute($request, $response);

        // we've opted out at the location level and not speicified any values for the marketing opt outs so should opt out of location and SMS and email
        $locationOptOut = $this->em->getRepository(LocationOptOut::class)->findOneBy(["profileId"=>"profile1", "serial" => "serial1"]);
        $this->assertNotNull($locationOptOut);
        $this->assertFalse($locationOptOut->deleted);

        $smsOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"sms"]));
        $this->assertNotNull($smsOptOut);
        $this->assertTrue($smsOptOut->optOut);
        $emailOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"email"]));
        $this->assertNotNull($emailOptOut);
        $this->assertTrue($emailOptOut->optOut);
    }

    public function testOptOutLocationAndMarketing() {
        $userRegistration = $this->createMock(UserRegistrationRepository::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $subject = new NearlyOptOut($this->logger, $this->em, $userRegistration, $this->queueSender);
        $request->method('getParsedBody')->willReturn([
            "data" => true,
            "serial" => "serial1",
            "marketing" => [
                "sms" => true,
                "email" => true
            ]
        ]);
        $request->method('getAttribute')->with('nearlyUser')->willReturn(["profileId"=>"profile1"]);

        $response->expects($this->once())->method('withJson')->with(Http::status(200, 'OK'), 200);
        $userRegistration->expects($this->once())->method('updateLocationOptOut')->with("profile1", "serial1", true);
        $userRegistration->expects($this->once())->method('updateMarketingOptOuts')->with("profile1", "serial1", true, true);

        $subject->optOutRoute($request, $response);

        // we've opted out at the location level and marketing as well
        $locationOptOut = $this->em->getRepository(LocationOptOut::class)->findOneBy(["profileId"=>"profile1", "serial" => "serial1"]);
        $this->assertNotNull($locationOptOut);
        $this->assertFalse($locationOptOut->deleted);

        $smsOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"sms"]));
        $this->assertNotNull($smsOptOut);
        $this->assertTrue($smsOptOut->optOut);
        $emailOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"email"]));
        $this->assertNotNull($emailOptOut);
        $this->assertTrue($emailOptOut->optOut);

    }

    public function testOptOutLocationAndOptInMarketing() {
        $userRegistration = $this->createMock(UserRegistrationRepository::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $subject = new NearlyOptOut($this->logger, $this->em, $userRegistration, $this->queueSender);
        $request->method('getParsedBody')->willReturn([
            "data" => true,
            "serial" => "serial1",
            "marketing" => [
                "sms" => false,
                "email" => false
            ]
        ]);
        $request->method('getAttribute')->with('nearlyUser')->willReturn(["profileId"=>"profile1"]);

        $response->expects($this->once())->method('withJson')->with(Http::status(200, 'OK'), 200);
        $userRegistration->expects($this->once())->method('updateLocationOptOut')->with("profile1", "serial1", true);
        $userRegistration->expects($this->once())->method('updateMarketingOptOuts')->with("profile1", "serial1", false, false);

        $subject->optOutRoute($request, $response);

        // we've opted out at the location level and marketing as well
        $locationOptOut = $this->em->getRepository(LocationOptOut::class)->findOneBy(["profileId"=>"profile1", "serial" => "serial1"]);
        $this->assertNotNull($locationOptOut);
        $this->assertFalse($locationOptOut->deleted);

        $smsOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"sms"]));
        $this->assertNotNull($smsOptOut);
        $this->assertFalse($smsOptOut->optOut);
        $emailOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"email"]));
        $this->assertFalse($emailOptOut->optOut);
    }

    public function testOptOptOutMarketinOnlyg() {
        $userRegistration = $this->createMock(UserRegistrationRepository::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $subject = new NearlyOptOut($this->logger, $this->em, $userRegistration, $this->queueSender);
        $request->method('getParsedBody')->willReturn([
            "serial" => "serial1",
            "marketing" => [
                "sms" => false,
                "email" => true
            ]
        ]);
        $request->method('getAttribute')->with('nearlyUser')->willReturn(["profileId"=>"profile1"]);

        $response->expects($this->once())->method('withJson')->with(Http::status(200, 'OK'), 200);
        $userRegistration->expects($this->once())->method('updateMarketingOptOuts')->with("profile1", "serial1", true, false);

        $this->queueSender->expects($this->once())->method('sendMessage')->with([
            'profileId' => 'profile1',
            'serial'    => 'serial1',
        ], QueueUrls::GDPR_NOTIFIER);
        $subject->optOutRoute($request, $response);

        $smsOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"sms"]));
        $this->assertNotNull($smsOptOut);
        $this->assertFalse($smsOptOut->optOut);
        $emailOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy((["uid"=>"profile1", "serial"=>"serial1", "type"=>"email"]));
        $this->assertTrue($emailOptOut->optOut);
    }
}
