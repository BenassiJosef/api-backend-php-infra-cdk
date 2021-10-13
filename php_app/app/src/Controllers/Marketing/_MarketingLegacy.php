<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 11/08/2017
 * Time: 02:05
 */

namespace App\Controllers\Marketing;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Marketing\Campaign\_AudienceController;
use App\Models\MarketingCampaignEvents;
use App\Models\MarketingCampaigns;
use App\Models\MarketingEventOptions;
use App\Models\MarketingEvents;
use App\Models\MarketingLocations;
use App\Models\MarketingMessages;
use App\Models\Marketing\MarketingOptOut;
use App\Models\Organization;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Package\Marketing\MarketingReportRepository;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use App\Utils\CacheEngine;
use App\Utils\Http;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

class _MarketingLegacy
{
    private $logger;
    private $em;
    private $marketingCache;
    private $userRegistrationRepository;
    private $gdprInfoPublisher;
    /**
     * @var OrganisationIdProvider
     */
    private $orgIdProvider;
    /**
     * @var OrganizationService
     */
    private $organisationService;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(
        Logger $logger,
        EntityManager $em,
        CacheEngine $marketingCache,
        UserRegistrationRepository $userRegistrationRepository,
        QueueSender $gdprInfoPublisher
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->marketingCache = $marketingCache;
        $this->userRegistrationRepository = $userRegistrationRepository;
        $this->gdprInfoPublisher = $gdprInfoPublisher;
        $this->orgIdProvider = new OrganisationIdProvider($this->em);
        $this->organisationService = new OrganizationService($this->em);
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function findActionRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $send = $this->findAction($orgId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getCampaignsRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');

        $search = null;
        $offset = 0;
        $serial = null;
        $queryParams = $request->getQueryParams();

        if (array_key_exists('offset', $queryParams)) {
            $offset = $queryParams['offset'];
        }

        if (array_key_exists('search', $queryParams)) {
            $search = $queryParams['search'];
        }

        if (array_key_exists('serial', $queryParams)) {
            $serial = $queryParams['serial'];
        }

        $send = $this->getCampaigns($orgId, $offset, $search, $serial);

        return $response->withJson($send, $send['status']);
    }

    public function deleteCampaignRoute(Request $request, Response $response)
    {
        $send = $this->deleteCampaign($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function marketingGuessCheckerRoute(Request $request, Response $response)
    {

        $body = $request->getParsedBody();
        $res = [];
        if (array_key_exists('serials', $body)) {
            $calc = new _AudienceController($this->em);

            // TODO: fix GDPR violation (don't trust the frontend)
            $profiles = $calc->getAudienceWithInOneMonth($body['serials']);

            foreach ($profiles as $profile) {
                if (!is_array($body['audience'])) {
                    return $response->withJson(Http::status(400, 'INVALID_AUDIENCE'), 400);
                }
                if ($calc->audienceCalculator($body['audience'], $profile) === true) {
                    $profile['joined'] = $dt = DateTime::createFromFormat('Y-m-d H:i:s', $profile['joined']);
                    $res[] = $profile;
                }
            }
        }

        $this->em->clear();

        return $response->withJson(Http::status(200, $res));
    }

    public function findAction(string $orgId)
    {

        $select = $this->em->createQueryBuilder()
            ->select('u', 'b')
            ->from(MarketingCampaigns::class, 'u')
            ->where('u.admin = :admin or u.organizationId = :orgId')
            ->andWhere('u.active = :true')
            ->andWhere('u.limit > 0 OR IS NULL')
            ->setParameter('orgId', $orgId)
            ->setParameter('true', 1)
            ->getQuery()
            ->getArrayResult();
        if (empty($select)) {
            return Http::status(204);
        }

        foreach ($select as $campaign) {
            if (array_key_exists('name', $campaign)) {
            } else {
            }
        }

        return Http::status(200, $select);
    }

    public function marketingGuessChecker(array $body)
    {
        $newDate = new DateTime();
        $audience = $this->em->createQueryBuilder()
            ->select(
                'u.id,
                        u.phone,
                        u.phoneValid,
                        u.email,
                        u.verified,
                        u.first,
                        u.last,
                        u.country,
                        u.gender,
                        u.birthDay,
                        u.birthMonth,
                        ud.serial,
                        MAX(ud.timestamp) as joined,
                        UNIX_TIMESTAMP(MAX(ud.lastupdate)) as lastupdate,
                        MAX(ud.lastupdate) as humanLast,
                        TIMESTAMPDIFF(SECOND, ud.timestamp, ud.lastupdate)) as uptime,
                        COUNT(ud.profileId) as connections'
            )
            ->from(UserData::class, 'ud')
            ->innerJoin(UserProfile::class, 'u', 'WITH', 'ud.profileId = u.id')
            ->where('ud.serial IN (:serials)')
            ->andWhere('ud.timestamp > :month')
            ->setParameter('month', $newDate->modify('-1 month'))
            ->setParameter('serials', $body['locations'])
            ->groupBy('u.id')
            ->orderBy('lastupdate', 'DESC')
            ->getQuery()
            ->getArrayResult();
        if (empty($audience)) {
            return Http::status(204);
        }

        return Http::status(200, $audience);
    }

    public function getCampaignRoute(Request $request, Response $response)
    {
        $id = $request->getAttribute('id');
        $orgId = $request->getAttribute('orgId');
        $organisation = $this->em->getRepository(Organization::class)->findOneBy([
            'id' => $orgId,
        ]);
        $send = $this->getCampaign($organisation, $id);

        return $response->withJson($send, $send['status']);
    }

    public function saveCampaignRoute(Request $request, Response $response)
    {
        $id = $request->getAttribute('id');
        $orgId = $request->getAttribute('orgId');
        $body = $request->getParsedBody();
        $organisation = $this->em->getRepository(Organization::class)->find($orgId);
        $send = $this->saveCampaign($organisation, $body);

        return $response->withJson($send);
    }

    public function getEventsRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $send = $this->getEvents($orgId);

        return $response->withJson($send);
    }

    public function getMessageRoute(Request $request, Response $response)
    {

        $id = $request->getAttribute('id');
        $send = $this->getMessage($id);

        return $response->withJson($send);
    }

    public function getMessagesRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $send = $this->getMessages($orgId);

        return $response->withJson($send);
    }

    public function getMessage(string $id)
    {
        $message = $this->findMessage($id);

        if (is_null($message)) {
            return Http::status(204);
        }

        $response = $message->getArrayCopy();

        return Http::status(200, $response);
    }

    public function getMessages(string $orgId)
    {
        $messages = $this->em->getRepository(MarketingMessages::class)->findBy([
            'organizationId' => $orgId,
        ]);
        if (is_null($messages)) {
            return Http::status(204);
        }
        $response = [];
        foreach ($messages as $message) {
            $response[] = $message->getArrayCopy();
        }

        return Http::status(200, $response);
    }

    public function optOutEmailRoute(Request $request, Response $response)
    {
        $send = $this->optEmail($request->getQueryParams());

        return $response->withJson($send, $send['status']);
    }

    public function optOutSMSRoute(Request $request, Response $response)
    {
        $send = $this->optOutSms($request->getParsedBody());

        return $response->withJson($send, $send['status']);
    }

    public function optOutSms(array $body)
    {
        $cancelPrefixes = [
            'NO',
            'STP',
            'END',
            'OPT',
        ];
        $startPrefixes = [
            'START',
            'RESUME',
        ];

        $isCancel = false;
        $isStart = false;

        foreach ($cancelPrefixes as $prefix) {
            if ($isCancel === false) {
                if (strpos($body['Text'], $prefix) !== false) {
                    $isCancel = true;
                }
            }
        }

        if ($isCancel === false) {
            foreach ($startPrefixes as $prefix) {
                if ($isStart === false) {
                    if (strpos($body['Text'], $prefix) !== false) {
                        $isStart = true;
                    }
                }
            }
        }

        if ($isStart || $isCancel) {
            $optOut = $this->em->createQueryBuilder()
                ->select('u.serial, u.profileId')
                ->from(MarketingEvents::class, 'u')
                ->where('u.optOutCode = :code')
                ->andWhere('u.eventto LIKE :to')
                ->setParameter('to', '%' . $body['From'] . '%')
                ->setParameter('code', $body['Text'])
                ->getQuery()
                ->getArrayResult();
            if (empty($optOut)) {
                return Http::status(404);
            }

            if ($optOut[0]['profileId'] === 0) {
                return Http::status(202, 'PREVIEWS_CAN_NOT_BE_CANCELLED');
            }

            $profileId = $optOut[0]['profileId'];
            $serial = $optOut[0]['serial'];
            if ($isCancel) {
                $newLocationOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy([
                    'uid' => $profileId,
                    'serial' => $serial,
                ]);

                if (is_null($newLocationOptOut)) {
                    $newLocationOptOut = new MarketingOptOut($profileId, $serial, 'sms');
                    $this->em->persist($newLocationOptOut);
                } else {
                    $newLocationOptOut->optOut = true;
                }
                $this->userRegistrationRepository->updateSMSOptOut($profileId, $serial, true);
            } elseif ($isStart) {
                $locationOptOut = $this->em->getRepository(MarketingOptOut::class)->findOneBy([
                    'uid' => $profileId,
                    'serial' => $serial,
                    'type' => 'sms',
                ]);
                $this->em->remove($locationOptOut);
                $this->userRegistrationRepository->updateSMSOptOut($profileId, $serial, false);
            }
            $this->em->flush();

            return Http::status(200);
        }

        return Http::status(400, 'NEITHER_A_STOP_OR_SEND');
    }

    public function getEventRoute(Request $request, Response $response)
    {
        $id = $request->getAttribute('id');
        $send = $this->getEvent($id);

        return $response->withJson($send);
    }

    public function optEmail(array $queryParams)
    {
        $profileId = $queryParams['uid'];
        $serial = $queryParams['serial'];
        $findMarketingForSerial = $this->em->getRepository(MarketingOptOut::class)->findOneBy([
            'serial' => $serial,
            'uid' => $profileId,
            'type' => 'email',
        ]);

        if (is_object($findMarketingForSerial)) {
            $findMarketingForSerial->optOut = true;
        } else {
            $marketingUser = new MarketingOptOut($profileId, $serial, 'email');
            $this->em->persist($marketingUser);
        }
        $this->userRegistrationRepository->updateEmailOptOut($profileId, $serial, true);
        $this->em->flush();

        $this->gdprInfoPublisher->sendMessage([
            'profileId' => $profileId,
            'serial' => $serial,
        ], QueueUrls::GDPR_NOTIFIER);

        return Http::status(200);
    }

    public function getEvent(string $id)
    {
        $event = $this->findEvent($id);

        if (is_null($event)) {
            return Http::status(204);
        }

        $response = $event->getArrayCopy();
        /** HOW TO ORDER BY POSITION?  */
        $eventOptions = $this->findOperands($event->id);
        $response['rules'] = [];
        foreach ($eventOptions as $rule) {
            if (is_numeric($rule->value)) {
                $rule->value = (int) $rule->value;
            }
            $response['rules'][] = $rule->getArrayCopy();
        }

        return Http::status(200, $response);
    }

    public function getEvents(string $orgId)
    {

        $response = [];

        $events = $this->em->getRepository(MarketingCampaignEvents::class)->findBy(
            ['organizationId' => $orgId]
        );
        if (is_null($events)) {
            return Http::status(204);
        }
        $res = [];
        foreach ($events as $event) {
            $res[$event->id] = $event->getArrayCopy();

            /** HOW TO ORDER BY POSITION?  */
            $eventOptions = $this->findOperands($event->id);
            $res[$event->id]['rules'] = [];
            foreach ($eventOptions as $rule) {
                if (is_numeric($rule->value)) {
                    $rule->value = (int) $rule->value;
                }
                $res[$event->id]['rules'][] = $rule->getArrayCopy();
            }
        }

        foreach ($res as $event) {
            $response[] = $event;
        }

        return Http::status(200, $response);
    }

    public function saveEventRoute(Request $request, Response $response)
    {

        $orgId = $request->getAttribute('orgId');
        $organisation = $this->em->getRepository(Organization::class)->find($orgId);
        $body = $request->getParsedBody();
        $send = $this->saveEvent($body, $organisation);

        return $response->withJson($send);
    }

    public function getCampaign(Organization $organization, string $id)
    {
        if (strpos($id, 'REVIEW_') !== false) {
            $criteria = Criteria::create()
                ->where(
                    new CompositeExpression(CompositeExpression::TYPE_AND, [
                        new Comparison('name', Comparison::EQ, $id),
                        new Comparison('organizationId', Comparison::EQ, $organization->getId()),
                    ])
                );
        } else {
            $criteria = Criteria::create()
                ->where(
                    new CompositeExpression(CompositeExpression::TYPE_AND, [
                        new Comparison('id', Comparison::EQ, $id),
                        new Comparison('organizationId', Comparison::EQ, $organization->getId()),
                    ])
                );
        }

        /**
         * @var MarketingCampaigns $campaigns
         */
        $campaigns = $this->em->getRepository(MarketingCampaigns::class)->matching($criteria);

        if (count($campaigns) == 0) {
            return Http::status(204);
        }

        $campaign = $campaigns[0];

        $response = [
            'campaign' => $campaign->getArrayCopy(),
            'event' => [],
            'message' => $campaign->getMessage() ? $campaign->getMessage()->getArrayCopy() : [],
        ];

        $response['campaign']['locations'] = [];
        $locations = $this->findLocations($campaign->id);
        foreach ($locations as $location) {
            $response['campaign']['locations'][] = $location->serial;
        }

        if ($campaign->eventId) {
            $event = $this->findEvent($campaign->eventId);

            if (!is_null($event)) {
                $response['event'] = $event->getArrayCopy();
                /** HOW TO ORDER BY POSITION?  */
                $eventOptions = $this->findOperands($event->id);
                $response['event']['rules'] = [];
                foreach ($eventOptions as $rule) {
                    if (is_numeric($rule->value)) {
                        $rule->value = (int) $rule->value;
                    }
                    $response['event']['rules'][] = $rule->getArrayCopy();
                }
            }
        }

        return Http::status(200, $response);
    }

    public function findEvent(string $id): MarketingCampaignEvents
    {
        return $this->em->getRepository(MarketingCampaignEvents::class)->find($id);
    }

    public function findOperands(string $eventId)
    {
        return $this->em->getRepository(MarketingEventOptions::class)->findBy(['eventId' => $eventId], [
            'position' => 'ASC',
        ]);
    }

    public function findMessage(string $messageId): ?MarketingMessages
    {
        return $this->em->getRepository(MarketingMessages::class)->find($messageId);
    }

    /**
     * @param Organization $organization
     * @param string $id
     * @return MarketingCampaigns|null
     */
    public function findCampaign(Organization $organization, string $id): ?MarketingCampaigns
    {
        $criteria = Criteria::create()
            ->where(
                new CompositeExpression(CompositeExpression::TYPE_AND, [
                    new Comparison('id', Comparison::EQ, $id),
                    new CompositeExpression(CompositeExpression::TYPE_OR, [
                        new Comparison('admin', Comparison::EQ, $organization->getOwnerId()),
                        new Comparison('organizationId', Comparison::EQ, $organization->getId()),
                    ]),
                ])
            );
        $campaigns = $this->em->getRepository(MarketingCampaigns::class)->matching($criteria);

        if (count($campaigns) == 0) {
            return null;
        }

        return $campaigns[0];
    }

    public function saveCampaign(Organization $organization, array $body = [])
    {
        if (isset($body['name'])) {
            if (strpos($body['name'], 'REVIEW_') !== false) {
                $campaignCheck = $this->getCampaign($organization, $body['name']);
                if ($campaignCheck['status'] === 200) {
                    $body['id'] = $campaignCheck['message']['campaign']['id'];
                }
            }
        }
        if (isset($body['id'])) {
            $campaign = $this->findCampaign($organization, $body['id']);
            if (is_null($campaign)) {
                return Http::status(204);
            }

            $locations = $this->findLocations($campaign->id);
            foreach ($locations as $location) {
                $this->em->remove($location);
            }
        } else {
            $campaign = new MarketingCampaigns(
                $organization
            );
            if (is_null($body['messageId'])) {
                $message = new MarketingMessages($organization);

                $this->em->persist($message);
                $campaign->setMessage($message);
            }
        }

        if (isset($body['messageId']) && !is_null($body['messageId'])) {
            $campaign->setMessage($this->findMessage($body['messageId']));
        }

        $canUpdate = [
            'name',
            'active',
            'hasLimit',
            'limit',
            'templateId',
            'eventId',
            'messageId',
            'filterId',
            'automation',
            'spendPerHead',
        ];
        foreach ($body as $key => $item) {
            if (in_array($key, $canUpdate)) {
                $campaign->$key = $item;
            }
        }

        $campaign->edited = new DateTime();

        $this->em->persist($campaign);

        if (array_key_exists('locations', $body)) {
            foreach ($body['locations'] as $location) {
                $l = new MarketingLocations($campaign->id, $location);
                $this->em->persist($l);
            }
        }

        $this->em->flush();

        $c = $campaign->getArrayCopy();
        $c['locations'] = $body['locations'];
        if ($campaign->automation && $campaign->active && strpos($body['name'], 'REVIEW_') === false) {
            $client = new QueueSender();
            $client->sendMessage([
                'notificationContent' => [
                    'objectId' => $campaign->id,
                    'title' => 'Campaign ' . isset($body['id']) ? 'updated' : 'created',
                    'kind' => 'campaign',
                    'link' => '/marketing/campaigns',
                    'campaignId' => $campaign->id,
                    'orgId' => $organization->getId(),
                    'message' => 'See how your campaign is performing',
                ],
            ], QueueUrls::NOTIFICATION);
        }

        return Http::status(200, $c);
    }

    public function findLocations(string $campaignId)
    {
        return $this->em->getRepository(MarketingLocations::class)->findBy(['campaignId' => $campaignId]);
    }

    public function saveEvent(array $body = [], Organization $organization = null)
    {
        if (is_null($body['spendPerHead'])) {
            $body['spendPerHead'] = 0;
        }

        if (array_key_exists('id', $body)) {
            $event = $this->findEvent($body['id']);
            if (!is_object($event)) {
                return Http::status(204);
            }
            $event->name = $body['name'];
            $event->spendPerHead = $body['spendPerHead'];
            $operands = $this->findOperands($event->id);

            foreach ($operands as $operand) {
                $this->em->remove($operand);
            }
        } else {
            $event = new MarketingCampaignEvents($body['name'], $organization, $body['spendPerHead']);
        }
        $rules = [];
        $this->em->persist($event);
        if (array_key_exists('rules', $body)) {
            foreach ($body['rules'] as $position => $rule) {
                $eventOperand = new MarketingEventOptions(
                    $event->id,
                    $rule['event'],
                    $rule['operand'],
                    $rule['value'],
                    $position
                );
                if (array_key_exists('condition', $rule)) {
                    $eventOperand->condition = $rule['condition'];
                }
                $this->em->persist($eventOperand);
                $rules[] = $eventOperand->getArrayCopy();
            }
        }

        $response = $event->getArrayCopy();
        $response['rules'] = $rules;
        $this->em->flush();

        return Http::status(200, $response);
    }

    public function saveMessageRoute(Request $request, Response $response)
    {
        $orgId = $request->getAttribute('orgId');
        $organization = $this->em->getRepository(Organization::class)->find($orgId);
        $body = $request->getParsedBody();
        $send = $this->saveMessage($body, $organization);

        return $response->withJson($send);
    }

    public function saveMessage(array $body = [], Organization $organization = null)
    {
        if (array_key_exists('id', $body)) {
            $message = $this->findMessage($body['id']);
            if (is_null($message)) {
                return Http::status(204);
            }
        } else {
            $message = new MarketingMessages($organization);
        }

        $shouldUpdate = [
            'subject',
            'name',
            'smsContents',
            'emailContents',
            'emailContentsJson',
            'smsSender',
            'sendToSms',
            'sendToEmail',
            'templateType',
        ];
        foreach ($body as $key => $item) {
            if (in_array($key, $shouldUpdate)) {
                $message->$key = $item;
            }
        }

        $this->em->persist($message);
        $this->em->flush();

        $this->marketingCache->delete('campaignMessages:' . $message->id);

        return Http::status(200, $message->getArrayCopy());
    }

    public function getCampaigns($orgId, $offset, $search, $serial)
    {
        $campaigns = $this->em->createQueryBuilder()
            ->select('u.id, u.active, u.name, u.automation, u.created, u.lastSentAt')
            ->from(MarketingCampaigns::class, 'u');
        if (!is_null($serial)) {
            $campaigns = $campaigns->join(MarketingLocations::class, 'l', 'WITH', 'u.id = l.campaignId
            AND l.serial = :serial');
        }
        $campaigns = $campaigns->where('u.organizationId = :orgId')
            ->andWhere('u.deleted = :false')
            ->andWhere('u.messageId IS NOT NULL')
            ->andWhere('u.name NOT LIKE :reviewCampaign')
            ->setParameter('reviewCampaign', 'REVIEW\_%')
            ->setParameter('orgId', $orgId)
            ->setParameter('false', false);
        if (!is_null($search)) {
            $campaigns = $campaigns->andWhere('u.name LIKE :search')
                ->setParameter('search', $search . '%');
        }
        if (!is_null($serial)) {
            $campaigns = $campaigns->setParameter('serial', $serial);
        }
        $campaigns = $campaigns->orderBy('u.created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(10);

        $results = new Paginator($campaigns);
        $results->setUseOutputWalkers(false);

        $campaigns = $results->getIterator()->getArrayCopy();

        if (empty($campaigns)) {
            return Http::status(200, []);
        }
        $report = new MarketingReportRepository($this->em);
        foreach ($campaigns as $key => $campaign) {
            $campaigns[$key]['report'] = $report->getCampaign($campaign['id']);
        }

        $return = [
            'campaigns' => $campaigns,
            'has_more' => false,
            'total' => count($results),
            'next_offset' => $offset + 10,
        ];

        if ($offset <= $return['total'] && count($campaigns) !== $return['total']) {
            $return['has_more'] = true;
        }

        return Http::status(200, $return);
    }

    public function migrateMessage($message, $admin)
    {
        $message = (object) $message;
        if (!property_exists($message, 'content') || !property_exists($message, 'sendTo')) {
            return null;
        }

        $message->content = (object) $message->content;
        $message->sendTo = (object) $message->sendTo;
        if (!property_exists($message, 'name')) {
            $message->name = 'Unknown Message Name';
        }
        $messageN = new MarketingMessages($admin, $message->name);
        $messageN->subject = $message->name;
        if (property_exists($message->content, 'sms')) {
            $messageN->smsContents = $message->content->sms;
        }
        if (property_exists($message->content, 'email')) {
            $messageN->emailContents = $message->content->email;
        }
        if (property_exists($message->sendTo, 'sms')) {
            $messageN->sendToSms = $message->sendTo->sms;
        }
        if (property_exists($message->sendTo, 'email')) {
            $messageN->sendToEmail = $message->sendTo->email;
        }
        if (property_exists($message, 'created')) {
            $messageN->created = new DateTime($message->created);
        }

        $this->em->persist($messageN);

        return $messageN->id;
    }

    public function migrateEvent($event, $admin)
    {
        $event = (object) $event;
        if (!property_exists($event, 'rules')) {
            return false;
        }
        if (!property_exists($event, 'name')) {
            $event->name = 'Unknown Event';
        }
        $org = $this->organisationService->getOrganizationForOwnerId($admin);
        $eventN = new MarketingCampaignEvents($event->name, $org, 0);
        if (property_exists($event, 'created')) {
            $eventN->created = new DateTime($event->created);
        }

        $this->em->persist($eventN);
        $i = 0;
        foreach ($event->rules as $rule) {
            $rule = (object) $rule;
            if (!property_exists($rule, 'operand')) {
                $rule->operand = '=';
            }
            if (!property_exists($rule, 'value')) {
                $rule->value = 100;
            }
            $newEOption = new MarketingEventOptions($eventN->id, $rule->event, $rule->operand, $rule->value, $i);
            if (property_exists($event, 'eventOperands') && count($event->rules) !== $i) {
                $newEOption->condition = $event->eventOperands[0];
            }
            $this->em->persist($newEOption);
            $i++;
        }

        return $eventN->id;
    }

    public function deleteCampaign(string $campaignId)
    {
        $findCampaign = $this->em->getRepository(MarketingCampaigns::class)->findOneBy([
            'id' => $campaignId,
        ]);

        if (is_null($findCampaign)) {
            return Http::status(204);
        }

        $findCampaign->deleted = 1;
        $this->em->flush();

        return Http::status(200, ['id' => $campaignId]);
    }
}
