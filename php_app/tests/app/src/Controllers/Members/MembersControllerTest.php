<?php
declare(strict_types=1);

namespace StampedeTests\app\src\Controllers\Members;

use App\Controllers\Auth\_oAuth2Controller;
use App\Controllers\Billing\Quotes\QuoteCreator;
use App\Controllers\Members\_MembersController;
use App\Models\NetworkAccessMembers;
use App\Models\Role;
use App\Package\Member\MemberService;
use App\Package\Organisations\LocationAccessChangeRequestProvider;
use App\Package\Organisations\LocationService;
use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

final class MembersControllerTest extends TestCase
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

    public function testCreateUserWithOKAccess()
    {
        $oauthServer                         = $this->createMock(\OAuth2\Server::class);
        $oauthController                     = $this->createMock(_oAuth2Controller::class);
        $locationAccessChangeRequestProvider = $this->createMock(LocationAccessChangeRequestProvider::class);
        $locationService                     = $this->createMock(LocationService::class);
        $quoteCreator                        = $this->createMock(QuoteCreator::class);
        $memberService                        = $this->createMock(MemberService::class);
        $controller                          = new _MembersController(
            $oauthServer,
            $this->em,
            $oauthController,
            $locationAccessChangeRequestProvider,
            $locationService,
            $memberService,
            $quoteCreator
        );
        $request                             = $this->createMock(Request::class);
        $response                            = $this->createMock(Response::class);

        $request->method("getAttribute")->willReturn([
            'uid'      => 'u123',
            'role'     => Role::LegacyAdmin,
            'reseller' => 'r123'
        ]);
        $request->method("getParsedBody")->willReturn([
            'email'    => 'moderator@test.com',
            'access'   => ['serial1', 'serial2'],
            'password' => 'password123',
            'reseller' => 'r123',
            'role'     => Role::LegacyModerator
        ]);

        EntityHelpers::createNetworkAccess($this->em, 'serial1', 'u123');
        EntityHelpers::createNetworkAccess($this->em, 'serial2', 'u123');

        $oauthController->method('checkToken')->willReturn(true);
        $controller->createUserRoute($request, $response);

        $networkAccessMembers = $this->em->getRepository(NetworkAccessMembers::class)->findAll();
        $this->assertCount(2, $networkAccessMembers);
    }
}