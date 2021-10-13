<?php


namespace App\Controllers\Marketing\Campaign;


use App\Controllers\SMS\RandomOptOutCodeGenerator;
use App\Models\MarketingEvents;
use App\Models\MarketingMessages;
use App\Models\Organization;
use App\Package\Async\BatchedQueue;
use App\Package\Async\Flusher;
use App\Package\Async\FlushException;
use App\Package\Billing\Exceptions\SMSTransactionInsufficientBalance;
use App\Package\Billing\SMSTransactions;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class CampaignSMSSender implements Flusher
{
    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var BatchedQueue $queue
     */
    private $queue;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var SMSTransactions $smsTransactions
     */
    private $smsTransactions;

    /**
     * CampaignSMSSender constructor.
     * @param Logger $logger
     * @param BatchedQueue $queue
     * @param EntityManager $entityManager
     */
    public function __construct(
        Logger $logger,
        BatchedQueue $queue,
        EntityManager $entityManager,
        SMSTransactions $smsTransactions
    ) {
        $this->logger        = $logger;
        $this->queue         = $queue;
        $this->entityManager = $entityManager;
        $this->smsTransactions = $smsTransactions;
    }


    public function sendSMS($campaignId, $profile, $campaign, MarketingMessages $message): void
    {
        $optOutCode = RandomOptOutCodeGenerator::getCode();

        /**
         *@var Organization $organization
         */
        $organization = $this->entityManager->getRepository(Organization::class)->find($message->getOrganizationId());
        if (!$this->smsTransactions->canDeductCredits($organization, 1)) {
            $this->logger->debug("{$campaign->id} has insufficiant balance to send to {$profile['phone']}");
            throw new SMSTransactionInsufficientBalance();
        };

        $this->smsTransactions->deductCredits($organization, 1, $profile['phone']);

        $this->logger->debug("{$campaign->id} sending SMS to {$profile['phone']}");
        $this
            ->queue
            ->sendMessageJson(
                [
                    'number'        => $profile['phone'],
                    'message'       => $message->smsContents,
                    'sender'        => $message->smsSender,
                    'serial'        => $profile['serial'],
                    'profileId'     => $profile['id'],
                    'campaignId'    => $campaignId,
                    'campaignAdmin' => $campaign->admin,
                    'eventId'       => $campaign->eventId,
                    'deduct'        => true,
                    'optOutCode'    => $optOutCode
                ]
            );

        $marketingEvent = new MarketingEvents(
            'sms',
            $profile['phone'],
            $profile['id'],
            $profile['serial'],
            $campaign->eventId,
            $campaignId,
            $optOutCode
        );
        $this->entityManager->persist($marketingEvent);
        $this->entityManager->flush();
    }

    /**
     * @throws FlushException
     */
    public function flush(): void
    {
        $this->queue->flush();
    }
}
