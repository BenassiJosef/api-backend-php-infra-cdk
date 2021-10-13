<?php


namespace App\Package\DataSources;


use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\InteractionSerial;
use App\Models\UserProfile;
use App\Package\Async\Queue;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\Statements\CandidateProfileInsertStatement;
use App\Package\DataSources\Statements\CandidateProfileOrganizationRegistrationStatement;
use App\Package\DataSources\Statements\EmailProfileStatement;
use App\Package\DataSources\Statements\EmailRegistrationSourceStatement;
use App\Package\DataSources\Statements\EmailUserRegistrationStatement;
use App\Package\DataSources\Statements\ProfileIdOrganizationRegistrationStatement;
use App\Package\DataSources\Statements\ProfileIdProfileStatement;
use App\Package\DataSources\Statements\ProfileIdRegistrationSourceStatement;
use App\Package\DataSources\Statements\ProfileIdUserRegistrationStatement;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Exception;
use Ramsey\Uuid\UuidInterface;

class ProfileInteraction implements ProfileSaver
{
    /**
     * @var InteractionRequest
     */
    private $interactionRequest;

    /**
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @var StatementExecutor $statementExecutor
     */
    private $statementExecutor;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Queue $notificationQueue
     */
    private $notificationQueue;

    /**
     * @var HookNotifier $hookNotifier
     */
    private $hookNotifier;

    /**
     * ProfileInteraction constructor.
     * @param InteractionRequest $interactionRequest
     * @param Interaction $interaction
     * @param StatementExecutor $statementExecutor
     * @param EntityManager $entityManager
     * @param Queue $notificationQueue
     * @param HookNotifier $hookNotifier
     */
    public function __construct(
        InteractionRequest $interactionRequest,
        Interaction $interaction,
        StatementExecutor $statementExecutor,
        EntityManager $entityManager,
        Queue $notificationQueue,
        HookNotifier $hookNotifier
    ) {
        $this->interactionRequest = $interactionRequest;
        $this->interaction        = $interaction;
        $this->statementExecutor  = $statementExecutor;
        $this->entityManager      = $entityManager;
        $this->notificationQueue  = $notificationQueue;
        $this->hookNotifier       = $hookNotifier;
    }


    /**
     * @return InteractionRequest
     */
    public function getInteractionRequest(): InteractionRequest
    {
        return $this->interactionRequest;
    }

    /**
     * @param CandidateProfile $profile
     * @param OptInStatuses|null $optInStatusesOverride
     * @throws Exception
     */
    public function saveCandidateProfile(CandidateProfile $profile, OptInStatuses $optInStatusesOverride = null)
    {
        $this->saveCandidateProfiles([$profile], $optInStatusesOverride);
        $this
            ->hookNotifier
            ->saveCandidateProfile(
                $this->interaction->getDataSource(),
                $this->interaction,
                $profile
            );
    }

    /**
     * @param CandidateProfile[] $candidateProfiles
     * @param OptInStatuses|null $optInStatusesOverride
     */
    public function saveCandidateProfiles(array $candidateProfiles, OptInStatuses $optInStatusesOverride = null)
    {
        if ($optInStatusesOverride !== null) {
            foreach ($candidateProfiles as $candidateProfile) {
                $candidateProfile->setOptInStatuses($optInStatusesOverride);
            }
        }
        $emails     = EmailProfileStatement::emailsFromCandidateProfiles($candidateProfiles);
        $statements = $this->candidateProfilesStatements($candidateProfiles, $emails);
        $this->statementExecutor->executeMultiple($statements);
    }

    /**
     * @param string $email
     * @param OptInStatuses|null $optInStatuses
     * @throws Exception
     */
    public function saveEmail(string $email, OptInStatuses $optInStatuses = null)
    {
        $this->saveEmails([$email], $optInStatuses);
        $this
            ->hookNotifier
            ->saveEmail(
                $this->interaction->getDataSource(),
                $this->interaction,
                $email
            );
    }

    /**
     * @param string[] $emails
     * @param OptInStatuses|null $optInStatuses
     */
    public function saveEmails(array $emails, OptInStatuses $optInStatuses = null)
    {
        if ($optInStatuses === null) {
            $optInStatuses = new OptInStatuses(true, true, true);
        }
        $candidateProfiles = CandidateProfileInsertStatement::emailsToCandidateProfiles($emails, $optInStatuses);
        $statements        = $this->candidateProfilesStatements($candidateProfiles, $emails);
        $this->statementExecutor->executeMultiple($statements);
    }

    /**
     * @param CandidateProfile[] $candidateProfiles
     * @param array $emails
     * @return array
     */
    private function candidateProfilesStatements(array $candidateProfiles, array $emails): array
    {
        return array_merge(
            [
                new CandidateProfileInsertStatement(
                    $candidateProfiles
                ),
                new EmailProfileStatement(
                    $this->interaction,
                    $emails
                ),
            ],
            $this->emailUserRegistrationStatement($emails),
            [
                new CandidateProfileOrganizationRegistrationStatement(
                    $this->interactionRequest->getOrganization(),
                    $candidateProfiles
                ),
            ],
            $this->emailRegistrationSourceStatement($emails),
        );
    }

    private function emailUserRegistrationStatement(array $emails): array
    {
        $serials = $this->interactionRequest->getSerials();
        if (count($serials) === 0) {
            return [];
        }
        return [
            new EmailUserRegistrationStatement(
                $emails,
                $this->interactionRequest->getSerials(),
                $this->interactionRequest->getVisits()
            ),
        ];
    }

    private function emailRegistrationSourceStatement(array $emails): array
    {
        return [
            new EmailRegistrationSourceStatement(
                $this->interactionRequest->getDataSource(),
                $this->interactionRequest->getOrganization(),
                $emails,
                $this->registrationSourceSerials(),
            ),
        ];
    }

    /**
     * @param int $profileId
     */
    public function saveProfileId(int $profileId)
    {
        $this->saveProfileIds([$profileId]);
        $this
            ->hookNotifier
            ->saveProfileId(
                $this->interaction->getDataSource(),
                $this->interaction,
                $profileId
            );
    }

    /**
     * @param int[] $profileIds
     */
    public function saveProfileIds(array $profileIds)
    {
        $statements = array_merge(
            [
                new ProfileIdProfileStatement(
                    $this->interaction,
                    $profileIds
                ),
            ],
            $this->profileUserRegistrationStatement($profileIds),
            [
                new ProfileIdOrganizationRegistrationStatement(
                    $this->interactionRequest->getOrganization(),
                    $profileIds
                ),
            ],
            $this->profileRegistrationSourceStatement($profileIds),
        );

        $this->statementExecutor->executeMultiple($statements);
    }

    /**
     * @return string[]
     */
    private function registrationSourceSerials(): array
    {
        return array_merge(
            $this->interactionRequest->getSerials(),
            [
                null,
            ]
        );
    }

    /**
     * @param int[] $profileIds
     * @return Statement[]
     */
    private function profileUserRegistrationStatement(array $profileIds): array
    {
        $serials = $this->interactionRequest->getSerials();
        if (count($serials) === 0) {
            return [];
        }
        return [
            new ProfileIdUserRegistrationStatement(
                $profileIds,
                $this->interactionRequest->getSerials()
            ),
        ];
    }

    /**
     * @param int[] $profileIds
     * @return Statement[]
     */
    private function profileRegistrationSourceStatement(array $profileIds): array
    {
        return [
            new ProfileIdRegistrationSourceStatement(
                $this->interactionRequest->getDataSource(),
                $this->interactionRequest->getOrganization(),
                $profileIds,
                $this->registrationSourceSerials(),
            ),
        ];
    }

    /**
     * @param UserProfile $userProfile
     */
    public function saveUserProfile(UserProfile $userProfile)
    {
        $this->saveUserProfiles([$userProfile]);
        $this
            ->hookNotifier
            ->saveUserProfile(
                $this->interaction->getDataSource(),
                $this->interaction,
                $userProfile
            );
    }

    /**
     * @param UserProfile[] $userProfiles
     */
    public function saveUserProfiles(array $userProfiles)
    {
        $profileIds = from($userProfiles)
            ->select(
                function (UserProfile $userProfile): int {
                    return $userProfile->getId();
                }
            )
            ->toArray();
        $this->saveProfileIds($profileIds);
    }

    /**
     * @return string[]
     */
    public function serials(): array
    {
        $interactionSerials = $this
            ->entityManager
            ->getRepository(InteractionSerial::class)
            ->findBy(
                [
                    'interactionId' => $this->interaction->getId(),
                ]
            );
        return from($interactionSerials)
            ->select(
                function (InteractionSerial $interactionSerial): string {
                    return $interactionSerial->getSerial();
                }
            )
            ->toArray();
    }

    /**
     * @return UserProfile[]
     */
    public function profiles(): array
    {
        $interactionProfiles = $this
            ->entityManager
            ->getRepository(InteractionProfile::class)
            ->findBy(
                [
                    'interactionId' => $this->interaction->getId(),
                ]
            );
        return from($interactionProfiles)
            ->select(
                function (InteractionProfile $interactionProfile): UserProfile {
                    return $interactionProfile->getProfile();
                }
            )
            ->toArray();
    }

    public function interactionId(): string
    {
        return $this->interaction->getId()->toString();
    }
}