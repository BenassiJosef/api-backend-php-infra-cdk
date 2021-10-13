<?php


namespace App\Controllers\Marketing\Campaign;

use App\Models\BouncedEmails;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEvents;
use App\Models\MarketingMessages;
use App\Package\Async\BatchedQueue;
use App\Package\Async\Flusher;
use App\Package\Async\FlushException;
use App\Package\Async\Queue;
use App\Package\DataSources\OptInService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class CampaignEmailSender implements Flusher
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
     * BaseCampaignEmailSender constructor.
     * @param Logger $logger
     * @param BatchedQueue $queue
     * @param EntityManager $entityManager
     */
    public function __construct(
        Logger $logger,
        BatchedQueue $queue,
        EntityManager $entityManager
    ) {
        $this->logger        = $logger;
        $this->queue         = $queue;
        $this->entityManager = $entityManager;
    }

    public function sendEmail(
        $campaignId,
        $profile,
        MarketingCampaigns $campaign,
        $messageId,
        MarketingMessages $message
    ): void {

        if (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $optInService = new OptInService($this->entityManager);
        $canSend = $optInService->canSendEmailToUserWithIds(
            $campaign->getOrganizationId()->toString(),
            $profile['id']
        );
 
        if (!$canSend) {
            return;
        }

        $findBounce = $this->entityManager->getRepository(BouncedEmails::class)->find($profile['email']);
        if (!is_null($findBounce)) {
            return;
        }

        $findEvent = $this->entityManager->getRepository(MarketingEvents::class)->findOneBy([
            'profileId' => $profile['id'],
            'campaignId' => $campaignId
        ]);
        if (!is_null($findEvent)) {
            return;
        }
        $email   = $profile['email'];
        $to      = $email;
        $subject = $message->subject;

        $this->logger->debug("{$campaign->id} sending email to {$profile['email']}");
        $eventOrFilter = $campaign->filterId;
        if ($campaign->automation === true) {
            $eventOrFilter = $campaign->eventId;
        }

        $marketingEvent = new MarketingEvents(
            'email',
            $profile['email'],
            $profile['id'],
            $profile['serial'],
            $eventOrFilter,
            $campaignId,
            ''
        );
        $this->entityManager->persist($marketingEvent);
        $this->entityManager->flush();

        $this->queue->sendMessageJson(
            [
                'to'            => $to,
                'name'          => $profile['first'] . ' ' . $profile['last'],
                'serial'        => $profile['serial'],
                'profile'       => $profile,
                'uid'           => $profile['id'],
                'templateType'  => $message->templateType,
                'campaignId'    => $campaign->id,
                'campaignAdmin' => $campaign->admin,
                'eventId'       => $eventOrFilter,
                'subject'       => $subject,
                'deduct'        => true,
                'messageId'     => $messageId,
                'profileId'     => $profile['id']
            ]
        );
    }

    /**
     * @throws FlushException
     */
    public function flush(): void
    {
        $this->queue->flush();
    }
}
