<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Integrations\RabbitMQ;

use App\Controllers\Integrations\RabbitMQ\OptOutWorker;
use App\Controllers\Nearly\NearlyProfileOptOut;
use App\Models\Locations\LocationOptOut;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Domains\Registration\UserRegistrationRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class OptOutWorkerTest extends TestCase
{
    private $em;
    private $logger;

    public function setUp(): void
    {
        $this->em = DoctrineHelpers::createEntityManager();
        $this->em->beginTransaction();

        $this->logger = $this->createMock(Logger::class);
    }

    public function tearDown(): void
    {
        $this->em->rollback();
    }

    private function common(Array $body)
    {
        $controller = new OptOutWorker($this->logger, $this->em, new UserRegistrationRepository($this->logger, $this->em), new NearlyProfileOptOut($this->em));
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $request->method('getParsedBody')->willReturn($body);

        $controller->runWorkerRoute($request, $response);
    }

    public function testNoGroupOptOutLocationAndMarket()
    {
        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>true
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'serial'=>"serial1", 'deleted'=>false
        ]);
        $this->assertCount(1, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(1, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(1, $marketingOptOutEmail);
    }

    public function testNoGroupOptOutLocationAndMarketExistingEntries()
    {
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial1", true);

        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "sms", false);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "email", false);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>true
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'serial'=>"serial1"
        ]);
        $this->assertCount(1, $locationOptOuts);
        $this->assertFalse($locationOptOuts[0]->deleted);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "type"=>"sms"]
        );
        $this->assertCount(1, $marketingOptOutSMS);
        $this->assertTrue($marketingOptOutSMS[0]->optOut);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "type"=>"email"]
        );
        $this->assertCount(1, $marketingOptOutEmail);
        $this->assertTrue($marketingOptOutEmail[0]->optOut);
    }

    public function testNoGroupOptOutLocationAndInMarket()
    {
        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>false
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'serial'=>"serial1", 'deleted'=>false
        ]);
        $this->assertCount(1, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(0, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(0, $marketingOptOutEmail);
    }

    public function testNoGroupOptOutLocationAndInMarketExistingEntries()
    {
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial1", false);

        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "email", true);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>false
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'serial'=>"serial1"
        ]);
        $this->assertCount(1, $locationOptOuts);
        $this->assertFalse($locationOptOuts[0]->deleted, "Location opt out should not be deleted");

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "type"=>"sms"]
        );
        $this->assertCount(1, $marketingOptOutSMS);
        $this->assertFalse($marketingOptOutSMS[0]->optOut, "Should be opted into sms");

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, 'serial'=>"serial1", "type"=>"email"]
        );
        $this->assertCount(1, $marketingOptOutEmail);
        $this->assertFalse($marketingOptOutEmail[0]->optOut, "Should be opted into email");
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGroupOptOutLocationAndMarket()
    {
        $oauthUser = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1');
        $org = EntityHelpers::createOrganisation($this->em, 'Test', $oauthUser);

        EntityHelpers::createLocationPolicyGroup($this->em, "GDPR Group", $org, [
            "serial1", "serial2", "serial3"
        ]);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>true
        ]);

        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'deleted'=>false
        ]);
        $this->assertCount(3, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(3, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(3, $marketingOptOutEmail);
    }

    public function testGroupOptOutLocationAndMarketExistingEntries()
    {
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial1", false);
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial2", false);
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial3", false);

        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "email", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial2", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial2", "email", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial3", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial3", "email", true);

        $oauthUser = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1');
        $org = EntityHelpers::createOrganisation($this->em, 'Test', $oauthUser);

        EntityHelpers::createLocationPolicyGroup($this->em, "GDPR Group", $org, [
            "serial1", "serial2", "serial3"
        ]);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>true
        ]);

        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'deleted'=>false
        ]);
        $this->assertCount(3, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(3, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(3, $marketingOptOutEmail);
    }

    public function testGroupOptOutLocationAndInMarket()
    {
        $oauthUser = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1');
        $org = EntityHelpers::createOrganisation($this->em, 'Test', $oauthUser);

        EntityHelpers::createLocationPolicyGroup($this->em, "GDPR Group", $org, [
            "serial1", "serial2", "serial3"
        ]);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>false
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'deleted'=>false
        ]);
        $this->assertCount(3, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(0, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(0, $marketingOptOutEmail);
    }

    public function testGroupOptOutLocationAndInMarketExistingEntries()
    {
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial1", false);
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial2", false);
        $locationOptOut = EntityHelpers::createLocationOptOut($this->em, "123", "serial3", false);

        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "email", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial2", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial2", "email", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial3", "sms", true);
        EntityHelpers::createMarketingOpt($this->em, 123, "serial3", "email", true);

        $oauthUser = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1');
        $org = EntityHelpers::createOrganisation($this->em, 'Test', $oauthUser);

        EntityHelpers::createLocationPolicyGroup($this->em, "GDPR Group", $org, [
            "serial1", "serial2", "serial3"
        ]);

        $this->common([
            "serial"=>"serial1",
            "id" => 123,
            "optOutLocation"=>true,
            "optOutMarketing"=>false
        ]);
        $locationOptOuts = $this->em->getRepository(LocationOptOut::class)->findBy([
            'profileId'=>123, 'deleted'=>false
        ]);
        $this->assertCount(3, $locationOptOuts);

        $marketingOptOutSMS = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"sms"]
        );
        $this->assertCount(0, $marketingOptOutSMS);

        $marketingOptOutEmail = $this->em->getRepository(MarketingOptOut::class)->findBy(
            ['uid'=>123, "optOut"=>true, "type"=>"email"]
        );
        $this->assertCount(0, $marketingOptOutEmail);
    }
}