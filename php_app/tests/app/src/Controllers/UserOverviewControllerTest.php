<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers;

use App\Models\Integrations\FilterEventList;
use App\Models\Integrations\IntegrationEventCriteria;
use App\Package\Filtering\UserFilter;
use Cassandra\Date;
use DateTime;
use PHPUnit\Framework\TestCase;
use App\Models\UserRegistration;
use App\Controllers\User\UserOverviewController;
use App\Models\UserProfile;
use Ramsey\Uuid\Uuid;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;
use Carbon\Carbon;

final class UserOverviewControllerTest extends TestCase
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

    public function testGetUsersNoResults(): void
    {
        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(Uuid::uuid4()->toString(), ['ABCDEFGHIJKL'], 0, []);
        $this->assertEquals(204, $results['status'], 'Should be 204 status code');
        $this->assertEquals("NO_USERS_FOUND", $results['message']);
    }

    public function testGetSomeUsers(): void
    {
        self::markTestSkipped();
        $up1 = EntityHelpers::createUser($this->em, "bob@bob.com", "123", "m");
        $up2 = EntityHelpers::createUser($this->em, "'jim@fixit.com'", "123", "m");

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id);
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id);

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));

        $results    = $controller->get(['123'], 1, [], null);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(2, $results['message']['totalUsers']);
    }

    public function testFilterUsers(): void
    {
        self::markTestSkipped();
        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'email', 'fixit', 'contains');

        $up1 = EntityHelpers::createUser($this->em, 'bob@bob.com', "123","m");
        $up2 = EntityHelpers::createUser($this->em, 'jim@fixit.com', "123", "m");

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id);
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id);

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));

        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
    }

    public function testOldUsers()
    {
        self::markTestSkipped();

        $up1 = EntityHelpers::createUser($this->em, 'bob@bob.com', "123", "m");
        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'));

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], null);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
    }

    public function testBirthdayToday()
    {
        self::markTestSkipped();

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'dob', 'today', '=');

        // today
        $bd = new \DateTime();
        $up1 = EntityHelpers::createUser($this->em, 'today@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // yesterday
        $bd = (new \DateTime())->sub(new \DateInterval('P1D'));
        $up2 = EntityHelpers::createUser($this->em, 'yesterday@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // tomorrow
        $bd = (new \DateTime())->add(new \DateInterval('P1D'));
        $up3 = EntityHelpers::createUser($this->em, 'tomorrow@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'));
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id, new \DateTime('2019-01-01'));
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id, new \DateTime('2019-01-01'));

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
        $this->assertEquals('today@bob.com', $results['message']['users'][0]['email']);
    }

    public function testBirthdayTomorrow()
    {
        self::markTestSkipped();

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em,  $org,'dob', 'tomorrow', '=');
        // today
        $bd = new \DateTime();
        $up1 = EntityHelpers::createUser($this->em, 'today@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // yesterday
        $bd = (new \DateTime())->sub(new \DateInterval('P1D'));
        $up2 = EntityHelpers::createUser($this->em, 'yesterday@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // tomorrow
        $bd = (new \DateTime())->add(new \DateInterval('P1D'));
        $up3 = EntityHelpers::createUser($this->em, 'tomorrow@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'));
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id, new \DateTime('2019-01-01'));
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id, new \DateTime('2019-01-01'));

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
        $this->assertEquals('tomorrow@bob.com', $results['message']['users'][0]['email']);
    }
    
    public function testBirthdayNextWeek()
    {
        self::markTestSkipped();

        // Stop here and mark this test as incomplete.
        $filter = EntityHelpers::createFilter($this->em, null, 'dob', 'week', '=');

        // today
        $bd = new Carbon();
        $up1 = EntityHelpers::createUser($this->em, 'today@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // next week
        $bd = $bd->startOfWeek()->add(new \DateInterval('P10D'));
        $up2 = EntityHelpers::createUser($this->em, 'nextweek@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // the following week
        $bd = $bd->startOfWeek()->add(new \DateInterval('P7D'));
        $up3 = EntityHelpers::createUser($this->em, 'fortnight@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'));
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id, new \DateTime('2019-01-01'));
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id, new \DateTime('2019-01-01'));

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
        $this->assertEquals('nextweek@bob.com', $results['message']['users'][0]['email']);
    }


    public function testBirthdayNextMonth()
    {
        self::markTestSkipped();

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'dob', 'month', '=');

        // today
        $bd = new Carbon();
        $up1 = EntityHelpers::createUser($this->em, 'today@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // next month
        $bd = $bd->startOfMonth()->add(new \DateInterval('P1M'))->add(new \DateInterval('P15D'));
        $up2 = EntityHelpers::createUser($this->em, 'nextmonth@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));
        // the following month
        $bd = $bd->add(new \DateInterval('P35D'));
        $up3 = EntityHelpers::createUser($this->em, 'followingmonth@bob.com', "123", "m", (int)($bd->format('m')), (int)($bd->format('d')));

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'));
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id, new \DateTime('2019-01-01'));
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id, new \DateTime('2019-01-01'));

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
        $this->assertEquals('nextmonth@bob.com', $results['message']['users'][0]['email']);
    }

    public function testConnections()
    {
        self::markTestSkipped();

        $owner = EntityHelpers::createOauthUser($this->em, 'bob@banana.com', 'password1', "", '');
        $org = EntityHelpers::createOrganisation($this->em, 'test', $owner);

        $filter = EntityHelpers::createFilter($this->em, $org, 'connections', '5', '>');

        $up1 = EntityHelpers::createUser($this->em, 'billy@no_mates', "123", "m");
        $up2 = EntityHelpers::createUser($this->em, 'popular@kid.com', "123", "m");
        $up3 = EntityHelpers::createUser($this->em, 'mental@man.com', "123", "m");

        $ur1 = EntityHelpers::createUserRegistration($this->em, '123', $up1->id, new \DateTime('2019-01-01'), 1);
        $ur2 = EntityHelpers::createUserRegistration($this->em, '123', $up2->id, new \DateTime('2019-01-01'), 10);
        $ur3 = EntityHelpers::createUserRegistration($this->em, '123', $up3->id, new \DateTime('2019-01-01'), 2);

        $controller = new UserOverviewController($this->em, new UserFilter($this->em));
        $results    = $controller->get(['123'], 1, [], $filter->id);
        $this->assertEquals(200, $results['status'], 'Should be 200 status code');
        $this->assertEquals(1, $results['message']['totalUsers']);
        $this->assertEquals('popular@kid.com', $results['message']['users'][0]['email']);
    }
}
