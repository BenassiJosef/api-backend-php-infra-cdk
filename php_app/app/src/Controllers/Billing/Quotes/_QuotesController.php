<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 10/02/2017
 * Time: 20:38
 */

namespace App\Controllers\Billing\Quotes;

use App\Controllers\Billing\Subscriptions\SubscriptionCreator;
use App\Controllers\Billing\Subscriptions\LocationSubscriptionController;
use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Models\Billing\Organisation\Subscriptions;
use App\Models\Billing\Organisation\SubscriptionsRequest;
use App\Models\Billing\Quotes\Quotes;
use App\Models\OauthUser;
use App\Models\Organization;
use App\Package\Billing\ChargebeeAPI;
use App\Package\Billing\Subscription;
use App\Package\Organisations\OrganizationService;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Response;
use Slim\Http\Request;

class _QuotesController implements QuoteCreator
{
    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var _MailController $mail
     */
    protected $mail;

    /**
     * @var SubscriptionCreator $subscriptionController
     */
    private $subscriptionController;

    /**
     * @var OrganizationService $organisationService
     */
    private $organisationService;

    private $chargebee;

    /**
     * _QuotesController constructor.
     * @param EntityManager $em
     * @param OrganizationService $organisationService
     * @param SubscriptionCreator|null $subscriptionController
     * @throws \phpmailerException
     */
    public function __construct(
        EntityManager $em,
        OrganizationService $organisationService,
        SubscriptionCreator $subscriptionController = null
    ) {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
        if ($subscriptionController === null) {
            $subscriptionController = new LocationSubscriptionController($em);
        }
        $this->chargebee              = new ChargebeeAPI();
        $this->subscriptionController = $subscriptionController;
        $this->organisationService    = $organisationService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getsRoute(Request $request, Response $response)
    {
        $resellerOrgId = $request->getAttribute("resellerOrgId");

        $send = $this->getQuotes($resellerOrgId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function publicGetRoute(Request $request, Response $response)
    {

        $id   = $request->getAttribute('id');
        $send = $this->publicGetQuote($id);

        $this->em->clear();

        return $response->withStatus(302)
                        ->withHeader('Location', $send['message']);
    }

    public function partnerGetRoute(Request $request, Response $response)
    {
        $id            = $request->getAttribute('id');
        $resellerOrgId = $request->getAttribute("resellerOrgId");
        $send          = $this->partnerGetQuote($resellerOrgId, $id);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function postRoute(Request $request, Response $response)
    {
        $resellerOrgId = $request->getAttribute('resellerOrgId');
        $customerOrgId = $request->getAttribute('customerOrgId');

        $resellerOrg = $this->organisationService->getOrganisationById($resellerOrgId);
        $customerOrg = $this->organisationService->getOrganisationById($customerOrgId);
        if ($resellerOrg === null || $customerOrg === null) {
            return $response->withStatus(404, 'Organisation not found');
        }


        $send = $this->createQuote($request, $resellerOrg, $customerOrg);

        return $response->withJson($send, $send['status']);
    }

    public function putRoute(Request $request, Response $response)
    {
        $resellerOrgId = $request->getAttribute('resellerOrgId');
        $customerOrgId = $request->getAttribute('customerOrgId');

        $resellerOrg = $this->organisationService->getOrganisationById($resellerOrgId);
        $customerOrg = $this->organisationService->getOrganisationById($customerOrgId);
        if ($resellerOrg === null || $customerOrg === null) {
            return $response->withStatus(404, 'Organisation not found');
        }


        $send = $this->updateQuote($request, $resellerOrg, $customerOrg);

        return $response->withJson($send, $send['status']);
    }

    public function sendQuoteRoute(Request $request, Response $response)
    {
        $quoteId = $request->getAttribute('id');
        $body    = $request->getParsedBody();
        $send    = $this->sendQuote($quoteId, $body);

        return $response->withJson($send, $send['status']);
    }

    public function createQuote(
        Request $request,
        Organization $resellerOrganisation,
        Organization $customerOrganisation
    ) {


        $subscriptionRequest = new SubscriptionsRequest($request);

        $subscription = $subscriptionRequest->getSubscription();
        $hostedPage   = $subscription->createSubscriptionHostedPage(
            $subscriptionRequest->getVenues(),
            $subscriptionRequest->getContacts(),
            $customerOrganisation
        );
        if ($hostedPage['status'] !== 200) {
            return $hostedPage;
        }

        $quote = new Quotes(
            $resellerOrganisation->getOwnerId()->toString(),
            $resellerOrganisation->getId(),
            $customerOrganisation->getOwnerId()->toString(),
            $customerOrganisation->getId(),
            $request->getParsedBodyParam('description', 0),
            $subscription->createHostedPagePayload(
                $subscriptionRequest->getVenues(),
                $subscriptionRequest->getContacts(),
                $customerOrganisation
            ),
            $hostedPage['message']['url'],
            $hostedPage['message']['expires_at']
        );

        $organisationSubscription = new Subscriptions(
            $customerOrganisation,
            '',
            $subscriptionRequest->getAddons(),
            $subscriptionRequest->getVenues(),
            $subscriptionRequest->getContacts(),
            $subscriptionRequest->getPlan(),
            $subscriptionRequest->getCurrency(),
            'pending',
            $subscriptionRequest->getAnnual(),
        );

        $this->em->persist($quote);
        $this->em->flush();

        $res = [
            'subscription' => $organisationSubscription->jsonSerialize(),
            'quote'        => $quote->getArrayCopy()
        ];
        return Http::status(200, $res);
    }

    public function publicGetQuote(string $id)
    {


        $quote = $this->em->getRepository(Quotes::class)->findOneBy(['id' => $id]);

        if (is_null($quote)) {
            return Http::status(404);
        }

        $now = new \DateTime();

        if ($now->getTimestamp() > $quote->expiresAt) {

            $updateHostedPage = $this->chargebee->hostedNewPage($quote->payload);

            if ($updateHostedPage['status'] !== 200) {
                return $updateHostedPage;
            }

            $quote->expiresAt  = $updateHostedPage['message']['expires_at'];
            $quote->hostedPage = $updateHostedPage['message']['url'];
            $quote->updatedAt  = $now;
            $this->em->persist($quote);
        }

        $this->em->flush();

        return Http::status(302, $quote->hostedPage);
    }

    public function partnerGetQuote(string $resellerOrgId, string $id)
    {
        /** @var Quotes $quote */
        $quote = $this->em->getRepository(Quotes::class)->findOneBy(
            [
                'id'                     => $id,
                'resellerOrganizationId' => $resellerOrgId
            ]
        );

        if (empty($quote)) {
            return Http::status(404);
        }

        $payload       = $quote->payload;
        $subscription  = $payload['subscription'];
        $plan          = explode("-", $subscription['planId'], 2)[0];
        $annual        = strpos($subscription['planId'], 'annual') !== false;
        $exploadedPlan = explode('-', $subscription['planId']);
        $currency      = strtoupper(end($exploadedPlan));
        $contacts      = 0;
        $venues        = 0;
        $addons        = [];
        if (array_key_exists('addons', $payload)) {
            foreach ($payload['addons'] as $addon) {
                $realId = explode("-", $addon['id'], 2)[0];
                if ($realId === 'contacts') {
                    $contacts = $addon['quantity'];
                    continue;
                }
                if ($realId === 'venues') {
                    $venues = $addon['quantity'];
                    continue;
                }
                $addons[] = $realId;
            }
        }

        /** @var Organization $organization */
        $organization             = $this
            ->em
            ->getReference(
                Organization::class,
                $quote->getCustomerOrganizationId()
            );
        $organisationSubscription = new Subscriptions(
            $organization,
            '',
            $addons,
            $contacts,
            $venues,
            $plan,
            $currency,
            'pending',
            $annual
        );

        $res = [
            'subscription' => $organisationSubscription->jsonSerialize(),
            'quote'        => $quote->getArrayCopy()
        ];

        return Http::status(200, $res);
    }

    public function getQuotes(string $resellerOrgId)
    {
        $results = $this->em->createQueryBuilder()
                            ->select(
                                'q.id, q.reseller, q.customer, q.description, q.payload, q.hostedPage, q.createdAt, q.updatedAt,
            q.expiresAt, o.name as company'
                            )
                            ->from(Quotes::class, 'q')
                            ->leftJoin(Organization::class, 'o', 'WITH', 'o.id = q.customerOrganizationId')
                            ->where('q.resellerOrganizationId = :resellerOrgId')
                            ->setParameter('resellerOrgId', $resellerOrgId)
                            ->orderBy('q.createdAt', 'DESC')
                            ->getQuery()
                            ->getArrayResult();

        if (empty($results)) {
            return Http::status(404, 'QUOTES_NOT_FOUND');
        }
        $res = [];
        foreach ($results as $value) {
            $res[] = $value;
        }

        return Http::status(200, $results);
    }

    public function updateQuote(
        Request $request,
        Organization $resellerOrganization,
        Organization $customerOrganization
    ) {
        $quote = $this->em->getRepository(Quotes::class)->findOneBy(
            [
                'id'                     => $request->getAttribute('id'),
                'resellerOrganizationId' => $resellerOrganization->getId(),
                'customerOrganizationId' => $customerOrganization->getId()
            ]
        );

        if (is_null($quote)) {
            return Http::status(400, 'COULD_NOT_LOCATE_QUOTE');
        }

        $subscriptionRequest = new SubscriptionsRequest($request);
        $subscription        = $subscriptionRequest->getSubscription();
        $hostedPage          = $subscription->createSubscriptionHostedPage(
            $subscriptionRequest->getVenues(),
            $subscriptionRequest->getContacts(),
            $customerOrganization
        );
        if ($hostedPage['status'] !== 200) {
            return $hostedPage;
        }


        $quote->description = $request->getParsedBodyParam('description', 0);
        $quote->payload     = $subscription->createHostedPagePayload(
            $subscriptionRequest->getVenues(),
            $subscriptionRequest->getContacts(),
            $customerOrganization
        );


        $organisationSubscription = new Subscriptions(
            $customerOrganization,
            '',
            $subscriptionRequest->getAddons(),
            $subscriptionRequest->getVenues(),
            $subscriptionRequest->getContacts(),
            $subscriptionRequest->getPlan(),
            $subscriptionRequest->getCurrency(),
            'pending',
            $subscriptionRequest->getAnnual(),
        );

        $updateHostedPage = $this->chargebee->hostedNewPage($quote->payload);

        if ($updateHostedPage['status'] !== 200) {
            return $updateHostedPage;
        }

        $quote->hostedPage = $updateHostedPage['message']['url'];
        $quote->expiresAt  = $updateHostedPage['message']['expires_at'];
        $quote->updatedAt  = new \DateTime();
        $this->em->persist($quote);
        $this->em->flush();

        $res = [
            'subscription' => $organisationSubscription->jsonSerialize(),
            'quote'        => $quote->getArrayCopy()
        ];

        return Http::status(200, $res);
    }

    public function sendQuote(string $id, array $params)
    {
        $user = $this->em->createQueryBuilder()
                         ->select('u', 'i')
                         ->from(Quotes::class, 'u')
                         ->join(OauthUser::class, 'i', 'WITH', 'u.customer = i.uid')
                         ->where('u.id = :id')
                         ->setParameter('id', $id)
                         ->getQuery()
                         ->getArrayResult();

        if (empty($user)) {
            return Http::status(404, 'FAILED_TO_FIND_QUOTE');
        }

        $link = '<a href="https://product.stampede.ai/accept/' . $id . '" style="text-decoration: none; border-top-left-radius: 25px; border-top-right-radius: 25px; border-bottom-right-radius: 25px; border-bottom-left-radius: 25px; padding: 10px 20px; color: rgb(255, 255, 255); background-color: #0064F7; font-size: 13px; text-transform: uppercase;" data-bgcolor="Buttons" data-color="Buttons" data-size="Buttons" data-max="26">View Quote</a>';

        $payload = $params['body'];
        $payload = str_replace('{{quote_link}}', $link, $payload);
        $payload = str_replace(
            [
                "\r\n",
                "\r",
                "\n"
            ], "<br />", $payload
        );
        $send[]  = [
            'to'   => $params['email'],
            'name' => $params['name']
        ];

        if ($params['sendReference'] === true) {
            $send[] = [
                'to'   => $user[1]['email'],
                'name' => $user[1]['first'] . ' ' . $user[1]['last']
            ];
        }

        $sendMail = $this->mail->send(
            $send,
            [
                'admin'     => $user[1]['uid'],
                'quoteLink' => $payload,
                'quoteDesc' => $user[0]['description']
            ],
            'SendQuote',
            'Quote For Subscription(s)'
        );

        if ($sendMail['status'] !== 200) {
            return Http::status(400, 'FAILED_TO_SEND_QUOTE');
        }

        $this->em->flush();

        return Http::status(200, 'QUOTE_SENT');
    }
}
