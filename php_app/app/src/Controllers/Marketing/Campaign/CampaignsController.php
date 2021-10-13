<?php

namespace App\Controllers\Marketing\Campaign;

use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\SMS\RandomOptOutCodeGenerator;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Locations\Marketing\Campaign;
use App\Models\Locations\Marketing\CampaignSerial;
use App\Package\Async\Queue;
use App\Package\Filtering\UnsupportedFilterOperation;
use App\Package\Filtering\UserFilter;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEvents;
use App\Models\MarketingMessages;
use App\Models\Marketing\MarketingOptOut;
use App\Package\Nearly\NearlyProfile;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use App\Package\RequestUser\UserProvider;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use voku\CssToInlineStyles\Exception;

class CampaignsController
{
    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var UserFilter $userFilter
     */
    protected $userFilter;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * @var Queue $campaignEligibilityQueue
     */
    private $campaignEligibility;

    /**
     * @var CampaignSMSSender $smsSender
     */
    private $smsSender;

    /**
     * @var CampaignEmailSender $emailSender
     */
    private $emailSender;

    /**
     * @var Queue $notification
     */
    private $notification;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * @var OrganzationService $organizationService
     */
    private $organisationService;

    public function __construct(
        Logger $logger,
        EntityManager $em,
        UserFilter $userFilter,
        UserProvider $userProvider,
        OrganizationProvider $organizationProvider,
        Queue $campaignEligibility,
        CampaignEmailSender $campaignEmailSender,
        CampaignSMSSender $campaignSMSSender,
        Queue $notification
    ) {
        $this->logger               = $logger;
        $this->em                   = $em;
        $this->userFilter           = $userFilter;
        $this->campaignEligibility  = $campaignEligibility;
        $this->smsSender            = $campaignSMSSender;
        $this->emailSender          = $campaignEmailSender;
        $this->notification         = $notification;
        $this->userProvider         = $userProvider;
        $this->organizationProvider = $organizationProvider;
        $this->organisationService  = new OrganizationService($this->em);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return int[]|Response
     * @throws Exception
     */
    public function sendRoute(Request $request, Response $response)
    {
        try {
            $body = $request->getParsedBody();
            if (!array_key_exists('campaignId', $body)) {
                return $response->withJson(Http::status(400, ["message" => "Missing campaignId in body"]));
            }
            $campaignEligibilityMessage = CampaignEligibilityMessage::fromArray(
                [
                    'version'          => CampaignEligibilityMessage::VERSION,
                    'campaignId'       => $body['campaignId'],
                    'serials'          => $request->getAttribute('user')['access'],
                    'userId'           => $this->userProvider->getUser($request)->getUid(),
                    'idempotencyToken' => Uuid::uuid4()->toString(),
                ]
            );

            $organization = $this->organizationProvider->organizationForRequest($request);
            /** @var MarketingCampaigns $campaign */
            $campaign = $this->getCampaignByOrganization(
                $campaignEligibilityMessage->getCampaignId(),
                $organization->getId()
            );
            if (is_null($campaign)) {
                return $response->withJson(Http::status(404, ["message" => "Unknown Campaign"]));
            }
            if (!$campaign->active) {
                return $response->withJson(Http::status(404, ["message" => "Campaign is not active"]));
            }
            if ($campaign->deleted) {
                return $response->withJson(Http::status(404, ["message" => "Campaign is deleted"]));
            }

            $campaignId = $campaignEligibilityMessage->getCampaignId();
            $this->logger->info("Sending campaign $campaign->name (${campaignId})");
            $sent = $this->sendCampaignAsync($campaignEligibilityMessage);
            $this->sentCampaign($campaign);
            return $response->withJson(
                HTTP::status(
                    200,
                    [
                        "sent"  => $sent,
                        'async' => $this->async,
                    ]
                )
            );
        } catch (UnsupportedFilterOperation $e) {
            $this->logger->error($e->getMessage());
            return HTTP::status(400, ["message" => $e->getMessage()]);
        }
    }

    private function sentCampaign(MarketingCampaigns $campaign): void
    {
        if (!extension_loaded('newrelic')) {
            return;
        }
        newrelic_record_custom_event(
            'MarketingCampaignSent',
            [
                'mode'           => 'bulk',
                'id'             => $campaign->getId(),
                'name'           => $campaign->getName(),
                'organizationId' => $campaign->getOrganizationId(),
            ]
        );
    }

    public function getCampaignByOrganization(string $campaignId, UuidInterface $organizationId): ?MarketingCampaigns
    {
        /** @var MarketingCampaigns | null $campaign */
        $campaign = $this
            ->em
            ->getRepository(MarketingCampaigns::class)
            ->findOneBy(
                [
                    'id'             => $campaignId,
                    'organizationId' => $organizationId,
                ]
            );
        return $campaign;
    }

    public function sendCampaignAsync(CampaignEligibilityMessage $message)
    {
        $this->campaignEligibility->sendMessageJson($message);
        return 0;
    }

    /**
     * @param MarketingCampaigns $campaign The campaign to send
     * @return int The number of messages send
     *
     * @throws UnsupportedFilterOperation if the campaign uses a filter with columns or operators that are not supported
     */
    public function sendCampaign(MarketingCampaigns $campaign): int
    {

        $organisation = $this
            ->organisationService
            ->getOrganisationById($campaign->getOrganizationId()->toString());
        $serials      = [];
        foreach ($organisation->getLocations() as $location) {
            $serials[] = $location->getSerial();
        }
        $numberOfSerials = count($serials);
        if ($numberOfSerials == 0) {
            $this->logger->warning("{$campaign->id} has no serials");

            return 0;
        }
        $this->logger->info("{$campaign->id} has {$numberOfSerials} serials");

        $profiles         = $this
            ->userFilter
            ->getProfiles($campaign->organizationId, $serials, $campaign->filterId, $campaign->id);
        $numberOfProfiles = count($profiles);
        $this->logger->info("{$campaign->id} Will send to {$numberOfProfiles} profiles");

        $campaignId = $campaign->id;
        $messageId  = $campaign->messageId;
        if (is_null($messageId)) {
            $this->logger->warning("{$campaign->id} has no message");

            return 0;
        }
        $message = $this->getMessage($messageId);

        // there's no message so we can't send anything...
        if (is_null($message)) {
            $this->logger->notice("{$campaign->id} - No message found for $messageId");

            return 0;
        }

        // message is not valid if sending to email and no subject
        if ($message->sendToEmail && is_null($message->subject)) {
            $this->logger->notice("{$campaign->id} message $messageId is sending an email message but is missing subject - aborted");

            return 0;
        }

        $limit    = $campaign->limit;
        $hasLimit = $campaign->hasLimit;
        if ($hasLimit) {
            $this->logger->info("{$campaign->id} has limit $limit");
        } else {
            $this->logger->info("{$campaign->id} is unlimited");
        }
        $sent      = 0;
        $smsSent   = 0;
        $emailSent = 0;
        foreach ($profiles as $profile) {
            // see if the user has opted out of sms or email
            $userOptOutCheck = $this->getUserOptOut($profile['id'], $campaign->getOrganizationId()->toString());
            if (is_null($userOptOutCheck)) {
                continue;
            }
            $sendSms   = $userOptOutCheck->getSmsOptIn();
            $sendEmail = $userOptOutCheck->getEmailOptIn();

            // send sms messages
            if ($message->sendToSms && $profile['phone'] && $sendSms) {
                $this->smsSender->sendSMS($campaignId, $profile, $campaign, $message);
                $smsSent++;
                $sent++;
                // check to see if we have exhausted the campaign
                if ($hasLimit && $sent >= $limit) {
                    break;
                }
            }
            // send email
            if ($message->sendToEmail && $sendEmail) {
                $this->emailSender->sendEmail($campaignId, $profile, $campaign, $messageId, $message);
                $emailSent++;
                $sent++;
                // check to see if we have exhausted the campaign
                if ($hasLimit && $sent >= $limit) {
                    break;
                }
            }
            if ($smsSent % 10) {
                $this->smsSender->flush();
            }
            if ($emailSent % 10) {
                $this->emailSender->flush();
            }
        }
        $this->emailSender->flush();
        $this->smsSender->flush();
        // decrease the campaign limit by the number of mesaages sent
        if ($hasLimit) {
            $this->decrementLimit($campaignId, $sent);
            // is the campaign exhausted?
            if ($sent >= $limit) {
                $this->logger->info('Deactivating campaign ' . $campaignId);
                $this->deActiveCampaign($campaignId);
            }
        }
        $this->logger->info('Send ' . $sent . ' messages for campaign' . $campaignId);

        $this->notification->sendMessageJson(
            [
                'notificationContent' => [
                    'objectId'   => $campaignId,
                    'title'      => 'Campaign sent',
                    'kind'       => 'campaign',
                    'link'       => '/marketing/campaigns',
                    'campaignId' => $campaignId,
                    'orgId'      => $campaign->organizationId,
                    'message'    => 'Campaign sent to ' . $sent . ' people, see performance'
                ]
            ]
        );

        return $sent;
    }

    public function getMessage(string $messageId): MarketingMessages
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(MarketingMessages::class, 'u')
            ->where('u.id = :campaignMessageId')
            ->setParameter('campaignMessageId', $messageId)
            ->getQuery()
            ->getSingleResult();
    }

    public function getUserOptOut(int $profileId, string $orgId): ?OrganizationRegistration
    {
        return $this->em->getRepository(OrganizationRegistration::class)->findOneBy(
            [
                'profileId'      => $profileId,
                'organizationId' => $orgId
            ]
        );
    }

    public function decrementLimit(string $campaignId, int $sent)
    {
        if ($campaignId !== 'PREVIEW') {
            $campaign = $this->em->getRepository(MarketingCampaigns::class)->findOneBy(
                [
                    'id' => $campaignId
                ]
            );

            if ($campaign->hasLimit === true) {
                $newLimit = max(0, $campaign->limit - $sent);
                $this->logger->debug("{$campaign->id} reducing campaign limit from {$campaign->limit} to {$newLimit}");
                $campaign->limit = $newLimit;
                $this->em->persist($campaign);
                $this->em->flush();
            }
        }
    }

    public function getCampaignLimit(string $campaignId): ?int
    {
        if ($campaignId !== 'PREVIEW') {
            $campaign = $this->em->getRepository(MarketingCampaigns::class)->findOneBy(
                [
                    'id' => $campaignId
                ]
            );

            if ($campaign->hasLimit === true) {
                return $campaign->limit;
            }
        }

        return null;
    }

    public function deActiveCampaign(string $campaignId)
    {
        $this->em->createQueryBuilder()
            ->update(MarketingCampaigns::class, 'i')
            ->set('i.active', ':false')
            ->where('i.id = :campaignId')
            ->setParameter('false', false)
            ->setParameter('campaignId', $campaignId)
            ->getQuery()
            ->execute();
    }
}
