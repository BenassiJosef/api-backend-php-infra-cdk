<?php

namespace StampedeTests\app\src\Package\Auth\ExternalServices;

use App\Models\Organization;
use App\Package\Auth\Access\User\UserRequestValidatorFactory;
use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\ExternalServices\AccessChecker;
use App\Package\Auth\ExternalServices\AccessCheckRequest;
use App\Package\Auth\Scopes\Scope;
use App\Package\Auth\Tokens\AccessTokenRepository;
use App\Package\Auth\Tokens\TokenFactory;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\ORM\EntityManager;
use Ergebnis\Http\Method;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use StampedeTests\Helpers\DoctrineHelpers;

class AccessCheckerTest extends TestCase
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;
    /**
     * @var Organization $organization
     */
    private $organization;

    protected function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();

        $this->organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Company Ltd'
                ]
            );
    }


    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }

    private function accessChecker(): AccessChecker
    {
        return new AccessChecker(
            new TokenFactory(
                $this->entityManager,
                new AccessTokenRepository(
                    $this->entityManager
                ),
                new UserRequestValidatorFactory(
                    new UserRoleChecker(
                        $this->entityManager
                    )
                )
            )
        );
    }

    public function testCheck()
    {
        $organizationId = $this->organization->getId();
        $segmentId      = Uuid::uuid1();
        /** @var AccessCheckerTestCase[] $testCases */
        $testCases     = [
            AccessCheckerTestCase::canAccess(
                'some.admin@stampede.ai',
                new AccessCheckRequest(
                    Scope::SERVICE_BACKEND,
                    Method::POST,
                    "/organisations/${organizationId}/segments/${segmentId}/send",
                    '/organisations/{orgId}/segments/{id}/send',
                    [
                        'orgId' => $organizationId->toString(),
                        'id'    => $segmentId->toString(),
                    ]
                ),
            ),
            AccessCheckerTestCase::canAccess(
                'root.admin@stampede.ai',
                new AccessCheckRequest(
                    Scope::SERVICE_BACKEND,
                    Method::POST,
                    "/organisations/${organizationId}/segments/${segmentId}/send",
                    '/organisations/{orgId}/segments/{id}/send',
                    [
                        'orgId' => $organizationId->toString(),
                        'id'    => $segmentId->toString(),
                    ]
                ),
            ),
            AccessCheckerTestCase::noAccess(
                'some.resold.admin@stampede.ai',
                new AccessCheckRequest(
                    Scope::SERVICE_BACKEND,
                    Method::POST,
                    "/organisations/${organizationId}/segments/${segmentId}/send",
                    '/organisations/{orgId}/segments/{id}/send',
                    [
                        'orgId' => $organizationId->toString(),
                        'id'    => $segmentId->toString(),
                    ]
                ),
            ),
        ];
        $accessChecker = $this->accessChecker();
        foreach ($testCases as $i => $testCase) {
            try {
                $response = $accessChecker
                    ->check(
                        $testCase->request(),
                        $testCase->getRequest()
                    );
                self::assertTrue(
                    $testCase->isExpectedCanAccess(),
                    "Exception not thrown, expected no access, case (${i})"
                );
                self::assertEquals($testCase->getToken(), $response->getToken()->getToken());
            } catch (ForbiddenException $exception) {
                self::assertFalse(
                    $testCase->isExpectedCanAccess(),
                    "Exception thrown, expected to be able to access route case (${i})"
                );
            }
        }
    }
}
