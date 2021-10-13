<?php

namespace StampedeTests\app\src\Package\Profile;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Async\Queue;
use App\Package\Async\QueueConfig;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\EmailingProfileInteractionFactory;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\StatementExecutor;
use App\Package\Profile\ProfileMerger;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class ProfileMergerTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function setUp(): void
    {
        /** @var EmailingProfileInteractionFactory $emailingProfileInteractionFactory */
        $emailingProfileInteractionFactory = self::getMockBuilder(EmailingProfileInteractionFactory::class)
                                                 ->disableOriginalConstructor()
                                                 ->getMock();
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();
        $interactionFactory = new ProfileInteractionFactory(
            $this->entityManager,
            new StatementExecutor($this->entityManager),
            new Queue(
                new QueueConfig()
            ),
            new _HooksController($this->entityManager),
            $emailingProfileInteractionFactory,
            new HookNotifier($this->entityManager)
        );
        /** @var Organization $organization */
        $organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Resold Company Ltd',
                ]
            );

        $wifiDataSource     = $interactionFactory->getDataSource('wifi');
        $interactionRequest = new InteractionRequest(
            $organization,
            $wifiDataSource,
            [
                "B8ERSVSCR9LA",
                "DFJJAKA5BZUN"
            ],
        );
        $interactionFactory
            ->makeProfileInteraction($interactionRequest)
            ->saveCandidateProfile(
                new CandidateProfile(
                    "alistair.judson@example.com"
                )
            );

        /** @var Organization $secondOrganization */
        $secondOrganization       = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Company Ltd',
                ]
            );
        $secondInteractionRequest = new InteractionRequest(
            $secondOrganization,
            $wifiDataSource,
            [
                "6M38FVUOMVAZ",
                "AWRT0GKAKZDA"
            ],
        );

        $interactionFactory
            ->makeProfileInteraction(
                new InteractionRequest(
                    $secondOrganization,
                    $wifiDataSource,
                    [
                        "6M38FVUOMVAZ"
                    ],
                )
            )
            ->saveCandidateProfile(
                new CandidateProfile(
                    "alistair.judson@example.com"
                )
            );

        $interactionFactory
            ->makeProfileInteraction($secondInteractionRequest)
            ->saveCandidateProfile(
                new CandidateProfile(
                    "alistair@example.com"
                )
            );
    }

    public function testMergeOrganizationRegistrations()
    {
        /** @var UserProfile $from */
        $from = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => 'alistair.judson@example.com'
                ]
            );

        /** @var UserProfile $to */
        $to            = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => 'alistair@example.com'
                ]
            );
        $profileMerger = new ProfileMerger($this->entityManager);
        $profileMerger->merge($from, $to);
        $this->entityManager->flush();
        $this->entityManager->clear(OrganizationRegistration::class);
        /** @var OrganizationRegistration[] $orgRegistrations */
        $orgRegistrations = $this
            ->entityManager
            ->getRepository(OrganizationRegistration::class)
            ->findBy(
                [
                    'profileId' => $to->getId(),
                ]
            );
        self::assertCount(2, $orgRegistrations);
    }


    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }


}
