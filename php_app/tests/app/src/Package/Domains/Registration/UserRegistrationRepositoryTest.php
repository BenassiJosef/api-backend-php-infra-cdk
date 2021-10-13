<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Package\Domains\Registration;

use App\Package\Domains\Registration\UserRegistrationRepository;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class UserRegistrationRepositoryTest extends TestCase
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

    public function testUpdateOptouts(): void
    {
        $profile    = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $ur         = EntityHelpers::createUserRegistration($this->em, 'serial1', $profile->id);
        $repository = new UserRegistrationRepository($this->logger, $this->em);

        $repository->updateMarketingOptOuts($profile->id, "serial1", true, true);
        $this->assertTrue($ur->getEmailOptOut());
        $this->assertTrue($ur->getSMSOptOut());

        $repository->updateMarketingOptOuts($profile->id, "serial1", true, false);
        $this->assertTrue($ur->getEmailOptOut());
        $this->assertFalse($ur->getSMSOptOut());

        $repository->updateMarketingOptOuts($profile->id, "serial1", false, false);
        $this->assertFalse($ur->getEmailOptOut());
        $this->assertFalse($ur->getSMSOptOut());
    }

    public function testUpdateSMSOptOut(): void
    {
        $profile    = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $ur         = EntityHelpers::createUserRegistration($this->em, 'serial1', $profile->id);
        $repository = new UserRegistrationRepository($this->logger, $this->em);
        $this->assertFalse($ur->getSMSOptOut());

        $repository->updateSMSOptOut($profile->id, 'serial1', true);
        $this->assertTrue($ur->getSMSOptOut());

        $repository->updateSMSOptOut($profile->id, 'serial1', false);
        $this->assertFalse($ur->getSMSOptOut());
    }

    public function testUpdateEmailOptOut(): void
    {
        $profile    = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $ur         = EntityHelpers::createUserRegistration($this->em, 'serial1', $profile->id);
        $repository = new UserRegistrationRepository($this->logger, $this->em);
        $this->assertFalse($ur->getEmailOptOut());

        $repository->updateEmailOptOut($profile->id, 'serial1', true);
        $this->assertTrue($ur->getEmailOptOut());

        $repository->updateEmailOptOut($profile->id, 'serial1', false);
        $this->assertFalse($ur->getEmailOptOut());
    }
}