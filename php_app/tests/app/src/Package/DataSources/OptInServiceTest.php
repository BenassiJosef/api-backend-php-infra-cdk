<?php

namespace StampedeTests\app\src\Package\DataSources;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Models\DataSources\DataSource;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Async\Queue;
use App\Package\Async\QueueConfig;
use App\Package\DataSources\CandidateProfile;
use App\Package\DataSources\EmailingProfileInteractionFactory;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\OptInService;
use App\Package\DataSources\OptInStatuses;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\StatementExecutor;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use StampedeTests\Helpers\EntityHelpers;

class OptInServiceTest extends TestCase
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();
        $organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Resold Company Ltd',
                ]
            );

        $wifiDataSource = $this
            ->entityManager
            ->getRepository(DataSource::class)
            ->findOneBy(
                [
                    'key' => 'wifi'
                ]
            );

        /** @var EmailingProfileInteractionFactory $emailingProfileInteractionFactory */
        $emailingProfileInteractionFactory = self::getMockBuilder(EmailingProfileInteractionFactory::class)
                                                 ->disableOriginalConstructor()
                                                 ->getMock();

        $factory          = new ProfileInteractionFactory(
            $this->entityManager,
            new StatementExecutor(
                $this->entityManager
            ),
            new Queue(new QueueConfig()),
            new _HooksController($this->entityManager),
            $emailingProfileInteractionFactory,
            new HookNotifier($this->entityManager)
        );
        $candidateProfile = new CandidateProfile('test@example.com');
        $candidateProfile->setOptInStatuses(OptInStatuses::optedIn());
        $interaction = $factory
            ->makeProfileInteraction(
                new InteractionRequest(
                    $organization,
                    $wifiDataSource,
                    [
                        'B8ERSVSCR9LA',
                        'DFJJAKA5BZUN',
                    ]
                )
            );
        $interaction->saveCandidateProfile($candidateProfile);
    }

    function tearDown(): void
    {
        $this->entityManager->rollback();
    }

    public function testCanSendEmailToUserAtLocationWithIds()
    {
        /** @var UserProfile $profile */
        $profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy(['email' => 'test@example.com']);

        $optInService = new OptInService($this->entityManager);
        self::assertTrue($optInService->canSendEmailToUserAtLocationWithIds('B8ERSVSCR9LA', $profile->getId()));

    }

    public function testCanSendSMSToUserAtLocationWithIds()
    {
        /** @var UserProfile $profile */
        $profile = $this->entityManager->getRepository(UserProfile::class)->findOneBy(['email' => 'test@example.com']);

        $optInService = new OptInService($this->entityManager);
        self::assertTrue($optInService->canSendEmailToUserAtLocationWithIds('B8ERSVSCR9LA', $profile->getId()));
    }
}
