<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Registration;

use App\Controllers\Registrations\_RegistrationsController;
use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Package\Filtering\UserFilter;
use Cassandra\Date;
use DateTime;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use App\Models\UserRegistration;
use App\Controllers\User\UserOverviewController;
use App\Models\UserProfile;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;
use Carbon\Carbon;

final class RegistrationsControllerTest extends TestCase
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

    public function testUpdateNearlyUserIgnoresNulls()
    {
        $user_profile = EntityHelpers::createUser(
            $this->em,
            'bob@banana.com',
            '123',
            'm');
        $this->em->clear();
        $controller = new _RegistrationsController($this->em);
        $controller->updateNearlyUser(
            [
                "id" => $user_profile->id,
                "email" => null,
                "phone" => "456"
            ]
        );
        $this->em->clear();
        $up = $this->em->getRepository(UserProfile::class)->findOneBy([
            "id" => $user_profile->id
        ]);
        $this->assertNotNull($up->email);
        $this->assertEquals("456", $up->phone);
    }

    public function testUpdateProfileIgnoresNulls()
    {
        $user_profile = EntityHelpers::createUser(
            $this->em,
            'bob@banana.com',
            '123',
            'm');
        $this->em->clear();
        $controller = new _RegistrationsController($this->em);
        $controller->updateProfile(
            [
                "id" => $user_profile->id,
                "email" => null,
                "phone" => "456"
            ],
            "serial1"
        );
        $this->em->clear();
        $up = $this->em->getRepository(UserProfile::class)->findOneBy([
            "id" => $user_profile->id
        ]);
        $this->assertNotNull($up->email);
        $this->assertEquals("456", $up->phone);
    }

}