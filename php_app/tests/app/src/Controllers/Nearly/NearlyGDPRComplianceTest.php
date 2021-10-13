<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Nearly;

use App\Controllers\Nearly\NearlyGDPRCompliance;
use App\Package\Domains\Registration\UserRegistrationRepository;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class NearlyGDPRComplianceTest extends TestCase
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

    // User has not made a choice about the location yet - the front end needs to think they have not opted in yet
    public function testCompliantNoLocationOptYet()
    {
        EntityHelpers::createUserRegistration($this->em, "serial1", 123);

        $controller = new NearlyGDPRCompliance($this->logger, $this->em, new UserRegistrationRepository($this->logger, $this->em));

        $result = $controller->compliant("serial1", "123");
        $this->assertFalse($result['location']);
        $this->assertFalse($result['marketing']);
    }

    // User has made a choice and opted into the location
    public function testCompliantLocationOptIn()
    {
        EntityHelpers::createUserRegistration($this->em, "serial1", 123);
        $loo = EntityHelpers::createLocationOptOut($this->em, "123", "serial1");
        $loo->deleted = true;
        $this->em->persist($loo);
        $this->em->flush();

        $controller = new NearlyGDPRCompliance($this->logger, $this->em, new UserRegistrationRepository($this->logger, $this->em));

        $result = $controller->compliant("serial1", "123");
        $this->assertTrue($result['location']);
        $this->assertTrue($result['marketing']);
    }

    // User has made a choice and opted out of the location
    public function testCompliantLocationOpOut()
    {
        EntityHelpers::createUserRegistration($this->em, "serial1", 123);

        $loo = EntityHelpers::createLocationOptOut($this->em, "123", "serial1");

        $controller = new NearlyGDPRCompliance($this->logger, $this->em, new UserRegistrationRepository($this->logger, $this->em));

        $result = $controller->compliant("serial1", "123");
        $this->assertFalse($result['location']);
        $this->assertFalse($result['marketing']);
    }

    // User has made a choice and opted into the location and out of marketing
    public function testCompliantLocationOptInMarketingOptOut()
    {
        EntityHelpers::createUserRegistration($this->em, "serial1", 123);
        $loo = EntityHelpers::createLocationOptOut($this->em, "123", "serial1");
        $loo->deleted = true;
        $this->em->persist($loo);
        $this->em->flush();

        // opt out of sms marketing
        $moo = EntityHelpers::createMarketingOpt($this->em, 123, "serial1", "sms");

        $controller = new NearlyGDPRCompliance($this->logger, $this->em, new UserRegistrationRepository($this->logger, $this->em));

        $result = $controller->compliant("serial1", "123");
        $this->assertTrue($result['location']);
        $this->assertFalse($result['marketing']);
    }
}
