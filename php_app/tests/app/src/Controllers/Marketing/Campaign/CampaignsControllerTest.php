<?php

declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Marketing\Campaign;

use App\Controllers\Marketing\Campaign\CampaignEmailSender;
use App\Controllers\Marketing\Campaign\CampaignsController;
use App\Controllers\Marketing\Campaign\CampaignSMSSender;
use App\Models\Locations\Marketing\CampaignSerial;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use App\Package\Async\Queue;
use App\Package\Async\QueueConfig;
use App\Package\Filtering\UserFilter;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Package\Organisations\OrganizationProvider;
use App\Package\RequestUser\UserProvider;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class CampaignsControllerTest extends TestCase
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

    public function testGetCampaignAllowed(): void
    {
        self::markTestSkipped('broke');
        $logger          = $this->createMock(Logger::class);
        $queueSenderStub = $this->createMock(QueueSender::class);
        $smsSenderStub = $this->createMock(CampaignSMSSender::class);
        $emailSenderSub = $this->createMock(CampaignEmailSender::class);

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');

        EntityHelpers::createNetworkAccess($this->em, 'serial1', $owner->getUid());

        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'email', 'bob', 'contains');

        $campaign = EntityHelpers::createCampaign(
            $this->em,
            $org,
            "test1",
            $filter->id,
            null,
            "message1",
            true,
            false,
            null,
            false
        );

        $queueStub = $this->createMock(Queue::class);

        $cc              = new CampaignsController(
            $logger,
            $this->em,
            new UserFilter($this->em),
            new UserProvider($this->em),
            new OrganizationProvider($this->em),
            $queueStub,
            $emailSenderSub,
            $smsSenderStub,
            $queueStub,
        );
        $fetchedCampaign = $cc->getCampaignByOrganization($campaign->id, $org->getId());
        $this->assertNotNull($fetchedCampaign);
    }

    public function testGetCampaignNotAllowed(): void
    {
        self::markTestSkipped('broke');

        $logger = $this->createMock(Logger::class);

        $queueSenderStub = $this->createMock(QueueSender::class);
        $smsSenderStub = $this->createMock(CampaignSMSSender::class);
        $emailSenderSub = $this->createMock(CampaignEmailSender::class);

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org1   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $otherUser = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');

        EntityHelpers::createNetworkAccess($this->em, 'serial1', $otherUser->getUid());

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'email', 'bob', 'contains');

        $campaign = EntityHelpers::createCampaign(
            $this->em,
            $org,
            "test1",
            $filter->id,
            null,
            "message1",
            true,
            false,
            null,
            false
        );

        $queueStub = $this->createMock(Queue::class);

        $cc              = new CampaignsController(
            $logger,
            $this->em,
            new UserFilter($this->em),
            new UserProvider($this->em),
            new OrganizationProvider($this->em),
            $queueStub,
            $emailSenderSub,
            $smsSenderStub,
            $queueStub,
        );
        $fetchedCampaign = $cc->getCampaignByOrganization($campaign->id, $org1->getId());
        $this->assertNull($fetchedCampaign);
    }


    public function testSendCampaign(): void
    {
        self::markTestSkipped('broke');

        $logger = $this->createMock(Logger::class);

        $queueSenderStub = $this->createMock(QueueSender::class);
        $smsSenderStub = $this->createMock(CampaignSMSSender::class);
        $emailSenderSub = $this->createMock(CampaignEmailSender::class);

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'email', 'fixit', 'contains');

        $message = EntityHelpers::createMarketingMessage($this->em, $org, "testMessage");

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $campaign = EntityHelpers::createCampaign(
            $this->em,
            $org,
            'test',
            $filter->id,
            null,
            $message->id,
            true,
            false,
            null,
            false
        );

        $up1 = EntityHelpers::createUser($this->em, 'bob@bob.com', "123", 'm');
        $up2 = EntityHelpers::createUser($this->em, 'jim@fixit.com', "456", 'f');
        $up3 = EntityHelpers::createUser($this->em, 'bob@fixit.com', "456", 'f');
        $up4 = EntityHelpers::createUser($this->em, 'arther@biily.com', "456", 'f');
        $up5 = EntityHelpers::createUser($this->em, 'already_sent@fixit.com', "456", 'f');

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id);
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id);
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id);
        $ur4 = EntityHelpers::createUserRegistration($this->em, '123', $up4->id);
        $ur5 = EntityHelpers::createUserRegistration($this->em, '123', $up5->id);


        $me = EntityHelpers::createMarketingEvent($this->em, $up5->id, '123', $campaign->id, new \DateTime());

        $ms = EntityHelpers::createCampaignSerial($this->em, $campaign, '123');

        $queueStub = $this->createMock(Queue::class);

        $cc   = new CampaignsController(
            $logger,
            $this->em,
            new UserFilter($this->em),
            new UserProvider($this->em),
            new OrganizationProvider($this->em),
            $queueStub,
            $emailSenderSub,
            $smsSenderStub,
            $queueStub,
        );
        $sent = $cc->sendCampaign($campaign);
        $this->assertEquals(4, $sent);
    }

    public function testSendCampaignWithCrossiteFilter(): void
    {
        self::markTestSkipped('broke');

        $logger = $this->createMock(Logger::class);
        $smsSenderStub = $this->createMock(CampaignSMSSender::class);
        $emailSenderSub = $this->createMock(CampaignEmailSender::class);

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'serial', "s1", '=', 'or');
        EntityHelpers::createFilterCriteria($this->em, $filter->id, 'serial', "s2", '=', 'or');

        $message = EntityHelpers::createMarketingMessage($this->em, $org, "testMessage");

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org   = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $campaign = EntityHelpers::createCampaign(
            $this->em,
            $org,
            "test",
            $filter->id,
            null,
            $message->id,
            true,
            false,
            null,
            false
        );

        $up1 = EntityHelpers::createUser($this->em, 'bob@bob.com', "123", 'm');
        $up2 = EntityHelpers::createUser($this->em, 'jim@fixit.com', "456", 'f');
        $up3 = EntityHelpers::createUser($this->em, 'bob@fixit.com', "456", 'f');
        $up4 = EntityHelpers::createUser($this->em, 'arther@biily.com', "456", 'f');
        $up5 = EntityHelpers::createUser($this->em, 'already_sent@fixit.com', "456", 'f');

        $ur1 = EntityHelpers::createUserRegistration($this->em, 's1', $up1->id, new \DateTime(), 5);
        $ur2 = EntityHelpers::createUserRegistration($this->em, 's1', $up2->id, new \DateTime(), 2);
        $ur3 = EntityHelpers::createUserRegistration($this->em, 's1', $up3->id, new \DateTime(), 2);
        $ur4 = EntityHelpers::createUserRegistration($this->em, 's1', $up4->id, new \DateTime(), 2);
        $ur5 = EntityHelpers::createUserRegistration($this->em, 's1', $up5->id, new \DateTime(), 1);

        $ur6 = EntityHelpers::createUserRegistration($this->em, 's2', $up1->id, new \DateTime(), 5);
        $ur7 = EntityHelpers::createUserRegistration($this->em, 's2', $up2->id, new \DateTime(), 5);
        $ur8 = EntityHelpers::createUserRegistration($this->em, 's2', $up3->id, new \DateTime(), 5);
        $ur9 = EntityHelpers::createUserRegistration($this->em, 's2', $up4->id, new \DateTime(), 5);
        $urA = EntityHelpers::createUserRegistration($this->em, 's2', $up5->id, new \DateTime(), 5);

        $ms = EntityHelpers::createCampaignSerial($this->em, $campaign, 's1');
        $ms = EntityHelpers::createCampaignSerial($this->em, $campaign, 's2');

        $queueStub = $this->createMock(Queue::class);

        $cc   = new CampaignsController(
            $logger,
            $this->em,
            new UserFilter($this->em),
            new UserProvider($this->em),
            new OrganizationProvider($this->em),
            $queueStub,
            $emailSenderSub,
            $smsSenderStub,
            $queueStub,
        );
        $sent = $cc->sendCampaign($campaign);
        $this->assertEquals(10, $sent);
    }


    public function testSendCampaignNullFilter(): void
    {
        self::markTestSkipped('broke');

        $logger = $this->createMock(Logger::class);

        $queueStub = $this->createMock(Queue::class);
        $smsSenderStub = $this->createMock(CampaignSMSSender::class);
        $emailSenderSub = $this->createMock(CampaignEmailSender::class);

        $owner   = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org     = EntityHelpers::createOrganisation($this->em, 'test', $owner);
        $message = EntityHelpers::createMarketingMessage($this->em, $org, "testMessage");

        $campaign = EntityHelpers::createCampaign(
            $this->em,
            $org,
            "test1",
            null,
            null,
            $message->id,
            true,
            false,
            null,
            false
        );

        $up1 = EntityHelpers::createUser($this->em, 'bob@bob.com', "123", 'm');
        $up2 = EntityHelpers::createUser($this->em, 'jim@fixit.com', "456", 'f');
        $up3 = EntityHelpers::createUser($this->em, 'bob@fixit.com', "456", 'f');
        $up4 = EntityHelpers::createUser($this->em, 'arther@biily.com', "456", 'f');
        $up5 = EntityHelpers::createUser($this->em, 'already_sent@fixit.com', "456", 'f');

        $ur1 = EntityHelpers::createUserRegistration($this->em, 's1', $up1->id, new \DateTime(), 5);
        $ur2 = EntityHelpers::createUserRegistration($this->em, 's1', $up2->id, new \DateTime(), 2);
        $ur3 = EntityHelpers::createUserRegistration($this->em, 's1', $up3->id, new \DateTime(), 2);

        $ur6 = EntityHelpers::createUserRegistration($this->em, 's2', $up1->id, new \DateTime(), 5);
        $ur7 = EntityHelpers::createUserRegistration($this->em, 's2', $up2->id, new \DateTime(), 5);
        $ur8 = EntityHelpers::createUserRegistration($this->em, 's2', $up3->id, new \DateTime(), 5);
        $ur9 = EntityHelpers::createUserRegistration($this->em, 's2', $up4->id, new \DateTime(), 5);
        $urA = EntityHelpers::createUserRegistration($this->em, 's2', $up5->id, new \DateTime(), 5);

        $ms = EntityHelpers::createCampaignSerial($this->em, $campaign, 's1');

        $cc   = new CampaignsController(
            $logger,
            $this->em,
            new UserFilter($this->em),
            new UserProvider($this->em),
            new OrganizationProvider($this->em),
            $queueStub,
            $emailSenderSub,
            $smsSenderStub,
            $queueStub,
        );
        $sent = $cc->sendCampaign($campaign);
        $this->assertEquals(6, $sent);
    }
}
