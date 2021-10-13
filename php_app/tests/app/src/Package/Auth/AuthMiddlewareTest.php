<?php

namespace StampedeTests\app\src\Package\Auth;

use App\Models\OauthAccessTokens;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\Role;
use App\Models\UserProfile;
use App\Package\Auth\Access\Config\OrgTypeRoleConfig;
use App\Package\Auth\Access\Config\RoleConfig;
use App\Package\Auth\Access\User\UserRequestValidatorFactory;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\ProfileSource;
use App\Package\Auth\Tokens\Exceptions\UnauthorizedException;
use App\Package\Auth\Tokens\TokenFactory;
use App\Package\Auth\UserSource;
use App\Package\Organisations\UserRoleChecker;
use App\Package\RequestUser\UserProvider;
use DateTime;
use Doctrine\ORM\EntityManager;
use Ergebnis\Http\Method;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Slim\Http\Uri;
use Slim\Route;
use StampedeTests\Helpers\DoctrineHelpers;

/**
 * Class AuthMiddlewareTest
 * @package StampedeTests\app\src\Package\Auth
 */
class AuthMiddlewareTest extends TestCase
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var OauthUser $user
     */
    private $user;

    /**
     * @var OauthUser $resoldUser
     */
    private $resoldUser;

    /**
     * @var UserProfile $profile
     */
    private $profile;

    protected function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();

        $this->user = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->findOneBy(
                [
                    'email' => 'some.admin@stampede.ai'
                ]
            );

        $this->resoldUser = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->findOneBy(
                [
                    'email' => 'some.resold.admin@stampede.ai'
                ]
            );

        $this->profile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => 'alistair.judson@stampede.ai',
                ]
            );
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }


    private static function environment(): Environment
    {
        return Environment::mock(
            [
                'determineRouteBeforeAppMiddleware' => false,
                'displayErrorDetails'               => true,
                'addContentLengthHeader'            => false,
            ]
        );
    }

    private static function request(string $method, string $url, array $headers = []): Request
    {
        $slimHeaders = Headers::createFromEnvironment(self::environment());
        $slimHeaders->replace($headers);
        return new Request(
            $method,
            Uri::createFromString($url),
            $slimHeaders,
            [],
            [],
            Utils::streamFor(null)
        );
    }

    private function middleware(): AuthMiddleware
    {
        $tokens = [
            new OauthAccessTokens(
                'user',
                'some-client',
                $this->user->getUid(),
                new DateTime('+1 hour'),
                'ALL'
            ),
            new OauthAccessTokens(
                'profile',
                'some-client',
                (string)$this->profile->getId(),
                new DateTime('+1 hour'),
                'ALL'
            )
        ];

        $dbTokens = from($this->fetchUsersByEmails())
            ->select(
                function (OauthUser $user): OauthAccessTokens {
                    return new OauthAccessTokens(
                        $user->getEmail(),
                        'some-client',
                        $user->getUid(),
                        new DateTime('+1 hour'),
                        'ALL'
                    );
                }
            )
            ->toArray();
        $tokens   = array_merge($tokens, $dbTokens);
        return new AuthMiddleware(
            $this->entityManager,
            new TokenFactory(
                $this->entityManager,
                new DummyTokenSource(
                    ...$tokens
                ),
                new UserRequestValidatorFactory(
                    new UserRoleChecker($this->entityManager)
                )
            )
        );
    }

    public function middlewareFromToken(OauthAccessTokens $token): AuthMiddleware
    {
        return new AuthMiddleware(
            $this->entityManager,
            new TokenFactory(
                $this->entityManager,
                new DummyTokenSource(
                    $token
                ),
                new UserRequestValidatorFactory(
                    new UserRoleChecker(
                        $this->entityManager
                    )
                )
            )
        );
    }

    /**
     * @param Request $request
     * @param string|null $pattern
     * @param array $arguments
     * @param callable|null $closure
     * @return Response
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    private function callMiddleware(
        Request $request,
        string $pattern = null,
        array $arguments = [],
        callable $closure = null
    ): Response {
        if ($closure === null) {
            $closure = function (Request $request, Response $response): Response {
                return $response->withJson("uh-oh");
            };
        }

        if ($pattern !== null) {
            $route   = new Route(
                $request->getMethod(),
                $pattern,
                $closure
            );
            $request = $request->withAttribute(
                'route',
                $route->setArguments($arguments)
            );
        }

        $authMiddleware = $this->middleware();
        return $authMiddleware(
            $request,
            new Response(),
            $closure
        );
    }

    public function testNoAuthHeader()
    {
        self::expectException(UnauthorizedException::class);
        $this->callMiddleware(
            self::request(Method::GET, 'http://localhost/test/foo/bar')
        );
    }

    public function testInvalidAuthHeader()
    {
        self::expectException(UnauthorizedException::class);
        $this->callMiddleware(
            self::request(
                Method::GET,
                'http://localhost/test/foo/bar',
                [
                    'Authorization' => ''
                ]
            )
        );
    }

    public function testExpiredToken()
    {
        self::expectException(UnauthorizedException::class);
        $this->callMiddleware(
            self::request(
                Method::GET,
                'http://localhost/test/foo/bar',
                [
                    'Authorization' => 'Bearer an-expired-token'
                ]
            )
        );
    }

    public function testUserTokenSucceeds()
    {
        $response = $this
            ->callMiddleware(
                $this->request(
                    Method::GET,
                    'http://localhost/test/foo/bar',
                    [
                        'Authorization' => 'Bearer user'
                    ]
                ),
            );
        self::assertEquals(StatusCode::HTTP_OK, $response->getStatusCode());
    }

    public function testUserCanAccessSelf()
    {
        $user    = $this->user;
        $userIds = [
            'me',
            'self',
            $user->getUid()
        ];

        foreach ($userIds as $userId) {
            $response = $this
                ->callMiddleware(
                    $this->request(
                        Method::GET,
                        "http://localhost/test/foo/${userId}",
                        [
                            'Authorization' => 'Bearer user'
                        ]
                    ),
                    '/test/foo/{userId}',
                    [
                        'userId' => $userId
                    ],
                    function (Request $request, Response $response) use ($user): Response {
                        /** @var UserSource $userSource */
                        $userSource = $request->getAttribute(UserSource::class);
                        self::assertNotNull($userSource);
                        self::assertEquals($user->getUid(), $userSource->getUser()->getUid());
                        return $response->withJson("uh-oh");
                    }
                );
            self::assertEquals(StatusCode::HTTP_OK, $response->getStatusCode());
        }
    }

    public function testUserCantAccessOtherUser()
    {
        $resoldUser   = $this->resoldUser;
        $resoldUserId = $resoldUser->getUid();
        self::expectException(ForbiddenException::class);

        $this
            ->callMiddleware(
                $this->request(
                    Method::GET,
                    'http://localhost/test/foo/me',
                    [
                        'Authorization' => 'Bearer user'
                    ]
                ),
                '/test/foo/{userId}',
                [
                    'userId' => $resoldUserId,
                ]
            );
    }

    public function testUserCanAccessUserIfAdminOfUser()
    {
        $resoldUser   = $this->resoldUser;
        $resoldUserId = $resoldUser->getUid();

        $this
            ->callMiddleware(
                $this->request(
                    Method::GET,
                    'http://localhost/test/foo/me',
                    [
                        'Authorization' => 'Bearer root.admin@stampede.ai'
                    ]
                ),
                '/test/foo/{userId}',
                [
                    'userId' => $resoldUserId,
                ],
                function (Request $request, Response $response) use ($resoldUserId): Response {
                    /** @var UserSource | null $userProvider */
                    $userProvider = $request->getAttribute(UserSource::class);
                    self::assertNotNull($userProvider);
                    $user = $userProvider->getUser();
                    self::assertEquals($resoldUserId, $user->getUid());
                    return $response->withJson($user);
                }
            );
    }

    /**
     * @param string[] $names
     * @return string[]
     */
    private function fetchOrganizationIdsByNames(array $names = []): array
    {
        return from($this->fetchOrganizationsByNames($names))
            ->select(
                function (Organization $organization): string {
                    return $organization->getId()->toString();
                },
                function (Organization $organization): string {
                    return $organization->getName();
                }
            )
            ->toArray();
    }

    /**
     * @param string[] $names
     * @return Organization[]
     */
    private function fetchOrganizationsByNames(array $names = []): array
    {
        $repository = $this
            ->entityManager
            ->getRepository(Organization::class);
        if (count($names) === 0) {
            /** @var Organization[] $organizations */
            $organizations = $repository
                ->findAll();
            return $organizations;
        }
        /** @var Organization[] $organizations */
        $organizations = $repository
            ->findBy(
                [
                    'name' => $names
                ]
            );
        return $organizations;
    }

    private function fetchUserIdsByEmails(array $emails = []): array
    {
        return from($this->fetchUsersByEmails($emails))
            ->select(
                function (OauthUser $user): string {
                    return $user->getUid();
                },
                function (OauthUser $user): string {
                    return $user->getEmail();
                }
            )
            ->toArray();
    }

    /**
     * @param string[] $emails
     * @return OauthUser[]
     */
    private function fetchUsersByEmails(array $emails = []): array
    {
        $repository = $this
            ->entityManager
            ->getRepository(OauthUser::class);
        if (count($emails) === 0) {
            /** @var OauthUser[] $users */
            $users = $repository->findAll();
            return $users;
        }
        /** @var OauthUser[] $users */
        $users = $repository
            ->findBy(
                [
                    'email' => $emails,
                ]
            );
        return $users;
    }

    public function testCanAccessTheCorrectOrganizations()
    {
        $allOrganizations = $this->fetchOrganizationIdsByNames();
        $accessMap        = [
            'root.admin@stampede.ai'        => $allOrganizations,
            'some.reseller@stampede.ai'     => $this->fetchOrganizationIdsByNames(
                [
                    'Some Resold Company Ltd',
                    'Reseller Ltd'
                ]
            ),
            'some.resold.admin@stampede.ai' => $this->fetchOrganizationIdsByNames(
                [
                    'Some Resold Company Ltd'
                ]
            ),
            'some.admin@stampede.ai'        => $this->fetchOrganizationIdsByNames(
                [
                    'Some Company Ltd'
                ]
            ),
            'some.marketeer@stampede.ai'    => $this->fetchOrganizationIdsByNames(
                [
                    'Some Company Ltd'
                ]
            )
        ];
        foreach ($accessMap as $userEmail => $organizationIds) {
            foreach ($allOrganizations as $organizationName => $organizationId) {
                try {
                    $this
                        ->callMiddleware(
                            $this->request(
                                Method::GET,
                                "http://localhost/test/foo/${organizationId}",
                                [
                                    'Authorization' => "Bearer ${userEmail}"
                                ]
                            ),
                            '/test/foo/{orgId}',
                            [
                                'orgId' => $organizationId,
                            ]
                        );
                    self::assertContains(
                        $organizationId,
                        $organizationIds,
                        "Able to access organization that should not be accessible"
                    );
                } catch (ForbiddenException $exception) {
                    self::assertNotContains(
                        $organizationId,
                        $organizationIds,
                        "Unable to access an organization that should be accessible"
                    );
                }
            }
        }
    }

    public function testCanAccessTheCorrectOrganizationsWithRoles()
    {
        $allOrganizations = $this->fetchOrganizationIdsByNames();
        $accessMap        = [
            'root.admin@stampede.ai'        => $allOrganizations,
            'some.reseller@stampede.ai'     => $this->fetchOrganizationIdsByNames(
                [
                    'Some Resold Company Ltd',
                    'Reseller Ltd'
                ]
            ),
            'some.resold.admin@stampede.ai' => $this->fetchOrganizationIdsByNames(
                [
                    'Some Resold Company Ltd'
                ]
            ),
            'some.admin@stampede.ai'        => $this->fetchOrganizationIdsByNames(
                [
                    'Some Company Ltd'
                ]
            ),
            'some.marketeer@stampede.ai'    => [],
        ];
        foreach ($accessMap as $userEmail => $organizationIds) {
            foreach ($allOrganizations as $organizationName => $organizationId) {
                try {
                    $request = $this->request(
                        Method::GET,
                        "http://localhost/test/foo/${organizationId}",
                        [
                            'Authorization' => "Bearer ${userEmail}"
                        ]
                    );
                    $request = $request->withAttribute(
                        RoleConfig::class,
                        new RoleConfig(
                            Role::LegacyAdmin,
                            Role::LegacyReseller,
                            Role::LegacySuperAdmin
                        )
                    );
                    $this
                        ->callMiddleware(
                            $request,
                            '/test/foo/{orgId}',
                            [
                                'orgId' => $organizationId,
                            ]
                        );
                    self::assertContains(
                        $organizationId,
                        $organizationIds,
                        "User (${userEmail}) able to access an organization (${organizationName}) that should not be accessible"
                    );
                } catch (ForbiddenException $exception) {
                    self::assertNotContains(
                        $organizationId,
                        $organizationIds,
                        "User (${userEmail}) unable to access an organization (${organizationName}) that should be accessible"
                    );
                }
            }
        }
    }

    public function testCanAccessTheCorrectLocations()
    {
        $allSerials = [
            '6M38FVUOMVAZ',
            'AWRT0GKAKZDA',
            'B8ERSVSCR9LA',
            'DFJJAKA5BZUN'
        ];
        $accessMap  = [
            'root.admin@stampede.ai'        => $allSerials,
            'some.reseller@stampede.ai'     => array_slice($allSerials, 2),
            'some.resold.admin@stampede.ai' => array_slice($allSerials, 2),
            'some.admin@stampede.ai'        => array_slice($allSerials, 0, 2),
            'some.marketeer@stampede.ai'    => array_slice($allSerials, 0, 2),
        ];
        foreach ($accessMap as $userEmail => $serials) {
            foreach ($allSerials as $serial) {
                try {
                    $this
                        ->callMiddleware(
                            $this->request(
                                Method::GET,
                                "http://localhost/test/foo/${serial}",
                                [
                                    'Authorization' => "Bearer ${userEmail}"
                                ]
                            ),
                            '/test/foo/{serial}',
                            [
                                'serial' => $serial,
                            ]
                        );
                    self::assertContains(
                        $serial,
                        $serials,
                        "User (${userEmail}) able to access a location (${serial}) that should not be accessible"
                    );
                } catch (ForbiddenException $exception) {
                    self::assertNotContains(
                        $serial,
                        $serials,
                        "User (${userEmail}) unable to access a (${serial}) location that should be accessible"
                    );
                }
            }
        }
    }

    public function testCanAccessTheCorrectLocationsWithRoles()
    {
        $allSerials = [
            '6M38FVUOMVAZ',
            'AWRT0GKAKZDA',
            'B8ERSVSCR9LA',
            'DFJJAKA5BZUN'
        ];
        $accessMap  = [
            'root.admin@stampede.ai'           => $allSerials,
            'another.root.admin@stampede.ai'   => $allSerials,
            'some.reseller@stampede.ai'        => array_slice($allSerials, 2),
            'another.reseller@stampede.ai'     => array_slice($allSerials, 2),
            'some.resold.admin@stampede.ai'    => array_slice($allSerials, 2),
            'another.resold.admin@stampede.ai' => array_slice($allSerials, 2),
            'some.admin@stampede.ai'           => array_slice($allSerials, 0, 2),
            'another.admin@stampede.ai'        => array_slice($allSerials, 0, 2),
            'some.marketeer@stampede.ai'       => [],
            'another.marketeer@stampede.ai'    => [],
        ];
        foreach ($accessMap as $userEmail => $serials) {
            foreach ($allSerials as $serial) {
                try {
                    $request = $this->request(
                        Method::GET,
                        "http://localhost/test/foo/${serial}",
                        [
                            'Authorization' => "Bearer ${userEmail}"
                        ]
                    );

                    $request = $request->withAttribute(
                        RoleConfig::class,
                        new RoleConfig(
                            Role::LegacyAdmin,
                            Role::LegacyReseller,
                            Role::LegacySuperAdmin
                        )
                    );

                    $this
                        ->callMiddleware(
                            $request,
                            '/test/foo/{serial}',
                            [
                                'serial' => $serial,
                            ]
                        );
                    self::assertContains(
                        $serial,
                        $serials,
                        "User (${userEmail}) able to access a location (${serial}) that should not be accessible"
                    );
                } catch (ForbiddenException $exception) {
                    self::assertNotContains(
                        $serial,
                        $serials,
                        "User (${userEmail}) unable to access a (${serial}) location that should be accessible"
                    );
                }
            }
        }
    }

    public function testOrgTypeAccess()
    {
        $accessMap = [
            'another.root.admin@stampede.ai'   => [
                Role::LegacySuperAdmin => Organization::$allTypes,
            ],
            'root.admin@stampede.ai'           => [
                Role::LegacySuperAdmin => Organization::$allTypes,
                Role::LegacyReseller   => Organization::$allTypes,
                Role::LegacyAdmin      => Organization::$allTypes,
                Role::LegacyModerator  => Organization::$allTypes,
                Role::LegacyReports    => Organization::$allTypes,
                Role::LegacyMarketeer  => Organization::$allTypes,
            ],
            'another.reseller@stampede.ai'     => [
                Role::LegacyReseller => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
            ],
            'some.reseller@stampede.ai'        => [
                Role::LegacySuperAdmin => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
                Role::LegacyReseller   => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
                Role::LegacyAdmin      => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
                Role::LegacyModerator  => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
                Role::LegacyReports    => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ],
                Role::LegacyMarketeer  => [
                    Organization::ResellerType,
                    Organization::DefaultType
                ]
            ],
            'some.resold.admin@stampede.ai'    => [
                Role::LegacySuperAdmin => [
                    Organization::DefaultType
                ],
                Role::LegacyReseller   => [
                    Organization::DefaultType
                ],
                Role::LegacyAdmin      => [
                    Organization::DefaultType
                ],
                Role::LegacyModerator  => [
                    Organization::DefaultType
                ],
                Role::LegacyReports    => [
                    Organization::DefaultType
                ],
                Role::LegacyMarketeer  => [
                    Organization::DefaultType
                ]
            ],
            'another.resold.admin@stampede.ai' => [
                Role::LegacyAdmin => [
                    Organization::DefaultType
                ]
            ],
            'another.admin@stampede.ai'        => [
                Role::LegacyAdmin => [
                    Organization::DefaultType
                ]
            ],
            'some.admin@stampede.ai'           => [
                Role::LegacySuperAdmin => [
                    Organization::DefaultType
                ],
                Role::LegacyReseller   => [
                    Organization::DefaultType
                ],
                Role::LegacyAdmin      => [
                    Organization::DefaultType
                ],
                Role::LegacyModerator  => [
                    Organization::DefaultType
                ],
                Role::LegacyReports    => [
                    Organization::DefaultType
                ],
                Role::LegacyMarketeer  => [
                    Organization::DefaultType
                ]
            ],
            'another.marketeer@stampede.ai'    => [
                Role::LegacyMarketeer => [
                    Organization::DefaultType
                ]
            ],
            'some.marketeer@stampede.ai'       => [
                Role::LegacySuperAdmin => [
                    Organization::DefaultType
                ],
                Role::LegacyReseller   => [
                    Organization::DefaultType
                ],
                Role::LegacyAdmin      => [
                    Organization::DefaultType
                ],
                Role::LegacyModerator  => [
                    Organization::DefaultType
                ],
                Role::LegacyReports    => [
                    Organization::DefaultType
                ],
                Role::LegacyMarketeer  => [
                    Organization::DefaultType
                ]
            ],
        ];
        foreach (Role::$allRoles as $legacyRole) {
            foreach (Organization::$allTypes as $organizationType) {
                foreach ($accessMap as $userEmail => $access) {
                    try {
                        $request = $this->request(
                            Method::GET,
                            'http://localhost/foo/bar/baz',
                            [
                                'Authorization' => "Bearer ${userEmail}"
                            ]
                        );
                        $request = $request->withAttribute(
                            OrgTypeRoleConfig::class,
                            new OrgTypeRoleConfig(
                                [
                                    $legacyRole
                                ],
                                $organizationType
                            )
                        );
                        $this->callMiddleware(
                            $request
                        );

                        self::assertArrayHasKey(
                            $legacyRole,
                            $access,
                            "User (${userEmail}) can access org type (${organizationType}) as role (${legacyRole})"
                        );
                    } catch (ForbiddenException $exception) {
                        self::assertNotContains(
                            $legacyRole,
                            $access,
                            "User (${userEmail}) cannot access org type (${organizationType}) as role (${legacyRole})"
                        );
                    }
                }
            }
        }
    }

    public function testCanAccessOwnProfile()
    {
        $profileId = $this->profile->getId();

        $profileIds = [
            'self',
            'me',
            (string)$profileId
        ];
        foreach ($profileIds as $id) {
            $request  = $this
                ->request(
                    Method::GET,
                    "http://localhost/foo/bar/${id}",
                    [
                        'Authorization' => 'Bearer profile'
                    ]
                );
            $response = $this
                ->callMiddleware(
                    $request,
                    '/foo/bar/{profileId}',
                    [
                        'profileId' => $id,
                    ],
                    function (Request $request, Response $response) use ($profileId): Response {
                        /** @var ProfileSource | null $profileSource */
                        $profileSource = $request->getAttribute(ProfileSource::class);
                        self::assertNotNull($profileSource);
                        $profile = $profileSource->getProfile();
                        self::assertEquals($profileId, $profile->getId());
                        return $response->withJson($profile);
                    }
                );
            self::assertEquals(StatusCode::HTTP_OK, $response->getStatusCode());
        }
    }

    public function testProfileCantAccessUrlWithoutProfileId()
    {
        self::expectException(ForbiddenException::class);
        $this
            ->callMiddleware(
                $this->request(
                    Method::GET,
                    'http://localhost/test/foo/bar',
                    [
                        'Authorization' => 'Bearer profile'
                    ]
                ),
            );
    }

    public function testMalformedProfileId()
    {
        self::expectException(ForbiddenException::class);
        $this
            ->callMiddleware(
                $this
                    ->request(
                        Method::GET,
                        "http://localhost/foo/bar/asdfg",
                        [
                            'Authorization' => 'Bearer profile'
                        ]
                    ),
                '/foo/bar/{profileId}',
                [
                    'profileId' => 'asdfg',
                ]
            );
    }

    public function testCantAccessAnotherUsersProfile()
    {
        self::expectException(ForbiddenException::class);
        $this
            ->callMiddleware(
                $this
                    ->request(
                        Method::GET,
                        "http://localhost/foo/bar/asdfg",
                        [
                            'Authorization' => 'Bearer profile'
                        ]
                    ),
                '/foo/bar/{profileId}',
                [
                    'profileId' => '1',
                ]
            );
    }

    public function testCanMakeRequestWithScope()
    {
        $requests = [
            'get'  => [
                'request'   => $this->request(
                    Method::GET,
                    "http://localhost/foo/bar/asdfg",
                    ['Authorization' => 'Bearer scope']
                ),
                'pattern'   => '/foo/bar/{orgId}',
                'arguments' => [
                    'orgId' => 'asdfg'
                ]
            ],
            'post' => [
                'request'   => $this->request(
                    Method::POST,
                    "http://localhost/foo/bar/asdfg",
                    ['Authorization' => 'Bearer scope']
                ),
                'pattern'   => '/foo/bar/{orgId}',
                'arguments' => [
                    'orgId' => 'asdfg'
                ]
            ]
        ];

        $testCases = [
            'ALL'                                                        => ['get', 'post'],
            'SYSTEM'                                                     => ['get', 'post'],
            'READ'                                                       => ['get'],
            'WRITE'                                                      => ['post'],
            'ALL:BACKEND'                                                => ['get', 'post'],
            'SYSTEM:BACKEND'                                             => ['get', 'post'],
            'READ:BACKEND'                                               => ['get'],
            'WRITE:BACKEND'                                              => ['post'],
            'ALL:ALL'                                                    => ['get', 'post'],
            'SYSTEM:ALL'                                                 => ['get', 'post'],
            'READ:ALL'                                                   => ['get'],
            'WRITE:ALL'                                                  => ['post'],
            'READ WRITE'                                                 => ['get', 'post'],
            'READ:ALL WRITE:ALL'                                         => ['get', 'post'],
            'READ:BACKEND WRITE:BACKEND'                                 => ['get', 'post'],
            'READ:BACKEND:FOO:BAR:ASDFG WRITE:BACKEND:FOO:BAR:ASDFG'     => ['get', 'post'],
            'READ:BACKEND:FOO:BAR:ASDFG'                                 => ['get'],
            'WRITE:BACKEND:FOO:BAR:ASDFG'                                => ['post'],
            'SYSTEM:BACKEND:FOO:BAR:ASDFG'                               => ['get', 'post'],
            'ALL:BACKEND:FOO:BAR:ASDFG'                                  => ['get', 'post'],
            'READ:BACKEND:FOO:BAR:{orgId} WRITE:BACKEND:FOO:BAR:{orgId}' => ['get', 'post'],
            'READ:BACKEND:FOO:BAR:{orgId}'                               => ['get'],
            'WRITE:BACKEND:FOO:BAR:{orgId}'                              => ['post'],
            'SYSTEM:BACKEND:FOO:BAR:{orgId}'                             => ['get', 'post'],
            'ALL:BACKEND:FOO:BAR:{orgId}'                                => ['get', 'post'],
            'READ:ALL:FOO:BAR:{orgId} WRITE:ALL:FOO:BAR:{orgId}'         => ['get', 'post'],
            'READ:ALL:FOO:BAR:{orgId}'                                   => ['get'],
            'WRITE:ALL:FOO:BAR:{orgId}'                                  => ['post'],
            'SYSTEM:ALL:FOO:BAR:{orgId}'                                 => ['get', 'post'],
            'ALL:ALL:FOO:BAR:{orgId}'                                    => ['get', 'post'],
            'READ:ALL:FOO:BAR WRITE:ALL:FOO:BAR'                         => ['get', 'post'],
            'READ:ALL:FOO:BAR'                                           => ['get'],
            'WRITE:ALL:FOO:BAR'                                          => ['post'],
            'SYSTEM:ALL:FOO:BAR'                                         => ['get', 'post'],
            'ALL:ALL:FOO:BAR'                                            => ['get', 'post'],
            'ALL:ALL:BAZ:QUX'                                            => [],
            ''                                                           => ['get', 'post'],
        ];

        $dummyClosure = function (Request $request, Response $response): Response {
            return $response->withJson('hello-world');
        };

        foreach ($testCases as $scope => $expectedRequests) {
            $middleware = $this->middlewareFromToken(
                new OauthAccessTokens(
                    'scope',
                    'some-client',
                    null,
                    new DateTime('+1 hour'),
                    $scope
                )
            );
            foreach ($requests as $requestName => $requestCase) {
                /** @var Request $request */
                $request   = $requestCase['request'];
                $pattern   = $requestCase['pattern'];
                $arguments = $requestCase['arguments'];
                $route     = new Route(
                    $request->getMethod(),
                    $pattern,
                    $dummyClosure
                );
                $route     = $route->setArguments($arguments);

                try {
                    $middleware(
                        $request->withAttribute('route', $route),
                        new Response(),
                        $dummyClosure
                    );
                    self::assertContains($requestName, $expectedRequests);
                } catch (ForbiddenException $exception) {
                    self::assertNotContains($requestName, $expectedRequests);
                }
            }
        }
    }
}
