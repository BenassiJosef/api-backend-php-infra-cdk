<?php

namespace StampedeTests\app\src\Package\DataSources;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use App\Package\Async\Queue;
use App\Package\Async\QueueConfig;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\EmailingProfileInteractionFactory;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\StatementExecutor;
use DateTime;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class ProfileInteractionFactoryTest extends TestCase
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var DataSource $dataSource
     */
    private $dataSource;

    /**
     * @var UserProfile $profile
     */
    private $profile;
    /**
     * @var DataSource | null
     */
    private $wifiDataSource;

    /**
     * @var EmailingProfileInteractionFactory $emailingProfileInteractionFactory
     */
    private $emailingProfileInteractionFactory;

    public function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();

        $this->organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Resold Company Ltd',
                ]
            );

        $this->dataSource = $this
            ->entityManager
            ->getRepository(DataSource::class)
            ->findOneBy(
                [
                    'key' => 'import'
                ]
            );

        $this->wifiDataSource = $this
            ->entityManager
            ->getRepository(DataSource::class)
            ->findOneBy(
                [
                    'key' => 'wifi'
                ]
            );

        $this->profile                           = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => 'alistair.judson@stampede.ai'
                ]
            );
        $this->emailingProfileInteractionFactory = self::getMockBuilder(EmailingProfileInteractionFactory::class)
                                                       ->disableOriginalConstructor()
                                                       ->getMock();
    }

    public function tearDown(): void
    {
        $this->entityManager->rollback();
    }

    public function testMakeProfileInteractionFromProfiles()
    {
        $factory            = new ProfileInteractionFactory(
            $this->entityManager,
            new StatementExecutor(
                $this->entityManager
            ),
            new Queue(new QueueConfig()),
            new _HooksController($this->entityManager),
            $this->emailingProfileInteractionFactory,
            new HookNotifier($this->entityManager)
        );
        $profileInteraction = $factory
            ->makeProfileInteraction(
                new InteractionRequest(
                    $this->organization,
                    $this->wifiDataSource,
                    [
                        '6M38FVUOMVAZ',
                        'AWRT0GKAKZDA'
                    ],
                    1
                )
            );

        $profileInteraction->saveUserProfiles([$this->profile]);
        $wifiInteractions = $this
            ->entityManager
            ->getRepository(Interaction::class)
            ->findBy(
                [
                    'organizationId' => $this->organization->getId()->toString(),
                    'dataSourceId'   => $this->wifiDataSource->getId()->toString(),
                ]
            );

        $numInteractions = count($wifiInteractions);
        self::assertEquals($numInteractions, 1);
    }


    public function testMakeProfileInteraction()
    {
        $candidateProfiles = [
            new CandidateProfile(
                'alistair.p.judson@example.com',
                'Alistair',
                'Judson',
                '07708224982',
                new DateTime()
            ),
            new CandidateProfile(
                'alistair@example.com',
                'Alistair',
                'Judson',
                '07708224982',
                new DateTime()
            ),
            new CandidateProfile(
                'alistair.judson@stampede.ai',
                'Alistair',
                'Judson',
                '07708224982',
                new DateTime()
            )
        ];

        $factory            = new ProfileInteractionFactory(
            $this->entityManager,
            new StatementExecutor(
                $this->entityManager
            ),
            new Queue(new QueueConfig()),
            new _HooksController($this->entityManager),
            $this->emailingProfileInteractionFactory,
            new HookNotifier($this->entityManager)
        );
        $profileInteraction = $factory
            ->makeProfileInteraction(
                new InteractionRequest(
                    $this->organization,
                    $this->dataSource,
                    [
                        '6M38FVUOMVAZ',
                        'AWRT0GKAKZDA'
                    ],
                    1
                )
            );

        $profileInteraction->saveCandidateProfiles($candidateProfiles);

        $gotEmails = from($profileInteraction->profiles())
            ->select(
                function (UserProfile $userProfile) {
                    return $userProfile->getEmail();
                },
                function (UserProfile $userProfile) {
                    return $userProfile->getId();
                }
            )->toArray();

        $expectedEmails = [
            'alistair.p.judson@example.com',
            'alistair@example.com',
            'alistair.judson@stampede.ai'
        ];

        self::assertEquals(array_values($gotEmails), $expectedEmails);

        /** @var OrganizationRegistration $organizationRegistration */
        $organizationRegistration = $this
            ->entityManager
            ->getRepository(OrganizationRegistration::class)
            ->findOneBy(
                [
                    'organizationId' => $this->organization->getId(),
                    'profileId'      => $this->profile->getId()
                ]
            );

        $siteRegistrations = from($organizationRegistration->getRegistrations()->toArray())
            ->where(
                function (RegistrationSource $registrationSource) {
                    return $registrationSource->getSerial() !== null;
                }
            )
            ->select(
                function (RegistrationSource $registrationSource) {
                    return $registrationSource->getInteractions();
                },
                function (RegistrationSource $registrationSource) {
                    return $registrationSource->getSerial();
                }
            )
            ->toArray();

        $expectedSiteRegistrations = [
            '6M38FVUOMVAZ' => 1,
            'AWRT0GKAKZDA' => 1
        ];
        self::assertEquals($expectedSiteRegistrations, $siteRegistrations);
    }

    public function testWithNoLocations()
    {
        self::expectNotToPerformAssertions();
        $factory            = new ProfileInteractionFactory(
            $this->entityManager,
            new StatementExecutor(
                $this->entityManager
            ),
            new Queue(new QueueConfig()),
            new _HooksController($this->entityManager),
            $this->emailingProfileInteractionFactory,
            new HookNotifier($this->entityManager)
        );
        $profileInteraction = $factory
            ->makeProfileInteraction(
                new InteractionRequest(
                    $this->organization,
                    $this->dataSource,
                    [],
                    1
                )
            );
        $profileInteraction->saveUserProfile($this->profile);
    }
}
