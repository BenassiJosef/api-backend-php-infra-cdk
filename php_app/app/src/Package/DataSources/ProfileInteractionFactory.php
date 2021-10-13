<?php


namespace App\Package\DataSources;


use App\Controllers\Integrations\Hooks\_HooksController;
use App\Models\DataSources\DataSource;
use App\Models\DataSources\Interaction;
use App\Package\Async\Queue;
use App\Package\DataSources\Hooks\AutoStampingHook;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\Statements\SerialStatement;
use App\Package\Loyalty\Events\NopNotifier;
use App\Package\Loyalty\OrganizationLoyaltyServiceFactory;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Exception;

class ProfileInteractionFactory
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var StatementExecutor $statementExecutor
     */
    private $statementExecutor;

    /**
     * @var Queue $notificationQueue
     */
    private $notificationQueue;

    /**
     * @var _HooksController $hooksController
     */
    private $hooksController;

    /**
     * @var EmailingProfileInteractionFactory $emailingProfileInteractionFactory
     */
    private $emailingProfileInteractionFactory;

    /**
     * @var HookNotifier $hookNotifier
     */
    private $hookNotifier;

    /**
     * ProfileInteractionFactory constructor.
     * @param EntityManager $entityManager
     * @param StatementExecutor $statementExecutor
     * @param Queue $notificationQueue
     * @param _HooksController $hooksController
     * @param EmailingProfileInteractionFactory $emailingProfileInteractionFactory
     * @param HookNotifier $hookNotifier
     */
    public function __construct(
        EntityManager $entityManager,
        StatementExecutor $statementExecutor,
        Queue $notificationQueue,
        _HooksController $hooksController,
        EmailingProfileInteractionFactory $emailingProfileInteractionFactory,
        HookNotifier $hookNotifier
    ) {
        $this->entityManager                     = $entityManager;
        $this->statementExecutor                 = $statementExecutor;
        $this->notificationQueue                 = $notificationQueue;
        $this->hooksController                   = $hooksController;
        $this->emailingProfileInteractionFactory = $emailingProfileInteractionFactory;
        $this->hookNotifier                      = $hookNotifier;
    }

    /**
     * @param InteractionRequest $request
     * @return ProfileInteraction
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function makeProfileInteraction(InteractionRequest $request): ProfileInteraction
    {
        $interaction = new Interaction(
            $request->getOrganization(),
            $request->getDataSource(),
        );
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
        $serials = $request->getSerials();
        if (count($serials) > 0) {
            $this->statementExecutor->execute(new SerialStatement($interaction, $request->getSerials()));
        }

        return new ProfileInteraction(
            $request,
            $interaction,
            $this->statementExecutor,
            $this->entityManager,
            $this->notificationQueue,
            $this->hookNotifier
        );
    }

    /**
     * @param InteractionRequest $request
     * @return NotifyingProfileInteraction
     * @throws DBALException
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function makeNotifyingProfileInteraction(InteractionRequest $request): NotifyingProfileInteraction
    {
        $profileInteraction = $this->makeProfileInteraction($request);
        return new NotifyingProfileInteraction(
            $this->entityManager,
            $profileInteraction,
            $this->notificationQueue,
            $this->hooksController
        );
    }

    /**
     * @param InteractionRequest $request
     * @return EmailingProfileInteraction
     */
    public function makeEmailingProfileInteraction(InteractionRequest $request): EmailingProfileInteraction
    {
        $profileInteraction = $this->makeNotifyingProfileInteraction($request);
        return $this->emailingProfileInteractionFactory->make(
            $profileInteraction,
            $profileInteraction->getProfileInteraction()
        );
    }

    /**
     * @param string $key
     * @return DataSource
     * @throws Exception
     */
    public function getDataSource(string $key): DataSource
    {
        /** @var DataSource | null $dataSource */
        $dataSource = $this
            ->entityManager
            ->getRepository(DataSource::class)
            ->findOneBy(
                [
                    'key' => $key,
                ]
            );
        if ($dataSource === null) {
            throw new Exception('DataSource not found');
        }
        return $dataSource;
    }
}