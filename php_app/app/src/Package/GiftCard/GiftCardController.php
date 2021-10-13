<?php


namespace App\Package\GiftCard;


use App\Controllers\Integrations\Mail\MailSender;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Models\AlreadyRedeemedException;
use App\Models\AlreadyRefundedException;
use App\Models\GiftCard;
use App\Models\GiftCardSettings;
use App\Models\Organization;
use App\Models\Role;
use App\Package\DataSources\CandidateProfile;
use App\Package\Exceptions\RequiredKeysException;
use App\Package\GiftCard\Exceptions\GiftCardNotFoundException;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\UserRoleChecker;
use App\Package\Pagination\PaginatedResponse;
use App\Package\Pagination\RepositoryPaginatedResponse;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use App\Package\PrettyIds\URL;
use App\Package\RequestUser\UserProvider;
use App\Utils\Http;
use DateInterval;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\Mapping\MappingException;
use Slim\Http\Request;
use Slim\Http\Response;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Stripe;
use Throwable;
use YaLinqo\Enumerable;

class GiftCardController
{
    /**
     * @var GiftCardService $giftCardFactory
     */
    private $giftCardFactory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var MailSender $mailSender
     */
    private $mailSender;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * @var UserRoleChecker $userRoleChecker
     */
    private $userRoleChecker;

    /**
     * @var IDPrettyfier $settingsIdPrettyfier
     */
    private $settingsIdPrettyfier;

    /**
     * @var IDPrettyfier $giftcardIdPrettyfier
     */
    private $giftcardIdPrettyfier;

    /**
     * @var GiftCardReportRepository $giftcardReportRepository
     */
    private $giftcardReportRepository;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * @var GiftCardSearchRepositoryFactory $giftCardSearchRepositoryFactory
     */
    private $giftCardSearchRepositoryFactory;

    /**
     * GiftCardController constructor.
     * @param GiftCardService $giftCardFactory
     * @param EntityManager $entityManager
     * @param MailSender $mailSender
     * @param UserProvider $userProvider
     * @param UserRoleChecker $userRoleChecker
     * @param OrganizationProvider|null $organizationProvider
     */
    public function __construct(
        GiftCardService $giftCardFactory,
        EntityManager $entityManager,
        MailSender $mailSender,
        UserProvider $userProvider,
        UserRoleChecker $userRoleChecker,
        ?OrganizationProvider $organizationProvider = null
    ) {
        if ($organizationProvider === null) {
            $organizationProvider = new OrganizationProvider($entityManager);
        }
        Stripe::setApiKey(getenv('stripe_key'));
        $this->giftCardFactory                 = $giftCardFactory;
        $this->entityManager                   = $entityManager;
        $this->mailSender                      = $mailSender;
        $this->userProvider                    = $userProvider;
        $this->userRoleChecker                 = $userRoleChecker;
        $this->settingsIdPrettyfier            = new URL();
        $this->giftcardIdPrettyfier            = new HumanReadable();
        $this->giftcardReportRepository        = new GiftCardReportRepository($entityManager);
        $this->organizationProvider            = $organizationProvider;
        $this->giftCardSearchRepositoryFactory = new GiftCardSearchRepositoryFactory($entityManager);
    }

    private function getGiftCardSettings(string $id): ?GiftCardSettings
    {
        /** @var GiftCardSettings | null $settings */
        $settings = $this
            ->entityManager
            ->getRepository(GiftCardSettings::class)
            ->find($this->settingsIdPrettyfier->unpretty($id));
        return $settings;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function search(Request $request, Response $response): Response
    {
        return $response->withJson(
            RepositoryPaginatedResponse::fromRequestAndRepository(
                $request,
                $this->giftCardSearchRepositoryFactory->paginatableRepository($request)
            )
        );
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GiftCardNotFoundException
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MappingException
     */
    public function changeOwner(Request $request, Response $response): Response
    {
        $giftCard = $this
            ->giftCardFactory
            ->changeOwner(
                $this->giftCardFromRequest($request),
                CandidateProfile::fromRequest($request)
            );
        if ($giftCard->status() === GiftCard::STATUS_ACTIVE) {
            $this->sendEmail($giftCard);
        }
        return $response->withJson($giftCard);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws GiftCardNotFoundException
     */
    public function resendEmail(Request $request, Response $response): Response
    {
        $giftCard = $this->giftCardFromRequest($request);
        if ($giftCard->status() === GiftCard::STATUS_ACTIVE) {
            $this->sendEmail($giftCard, $request->getParam('email'));
        }
        return $response->withJson($giftCard);
    }

    private function organizationFromRequest(Request $request): Organization
    {
        return $this->organizationProvider->organizationForRequest($request);
    }

    /**
     * @param Request $request
     * @return GiftCard
     * @throws GiftCardNotFoundException
     */
    private function giftCardFromRequest(Request $request): GiftCard
    {
        $organization     = $this->organizationFromRequest($request);
        $giftCardIdString = $request->getAttribute('id');
        $giftCardId       = $this->giftcardIdPrettyfier->unpretty($giftCardIdString);
        /** @var GiftCard | null $giftCard */
        $giftCard = $this
            ->entityManager
            ->getRepository(GiftCard::class)
            ->findOneBy(
                [
                    'id'             => $giftCardId,
                    'organizationId' => $organization->getId(),
                    'redeemedAt'     => null,
                    'refundedAt'     => null,
                ]
            );
        if ($giftCard === null) {
            throw new GiftCardNotFoundException($giftCardIdString);
        }
        return $giftCard;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws DBALException
     * @throws Exceptions\AlreadyRedeemedException
     * @throws GiftCardNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function redeemGiftCard(Request $request, Response $response): Response
    {
        $giftCardId = $request->getAttribute("id");
        $user       = $this->userProvider->getOauthUser($request);
        /** @var GiftCard | null $giftCard */
        $giftCard = $this
            ->entityManager
            ->getRepository(GiftCard::class)
            ->find($this->giftcardIdPrettyfier->unpretty($giftCardId));
        if ($giftCard === null) {
            throw new GiftCardNotFoundException($giftCardId);
        }
        $orgIdString       = $giftCard
            ->getOrganizationId()
            ->toString();
        $canAccessGiftCard = $this
            ->userRoleChecker
            ->hasAccessToOrganizationAsRole($user, $orgIdString, Role::$allRoles);
        if (!$canAccessGiftCard) {
            throw new GiftCardNotFoundException($giftCardId);
        }
        $giftCard->redeem($user);
        $this->entityManager->persist($giftCard);
        $this->entityManager->flush();
        return $response->withJson(Http::status(200, $giftCard), 200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws DBALException
     * @throws Exceptions\AlreadyRefundedException
     * @throws GiftCardNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function refundGiftCard(Request $request, Response $response): Response
    {
        $giftCardId = $request->getAttribute("id");
        $user       = $this->userProvider->getOauthUser($request);
        /** @var GiftCard | null $giftCard */
        $giftCard = $this
            ->entityManager
            ->getRepository(GiftCard::class)
            ->find($this->giftcardIdPrettyfier->unpretty($giftCardId));
        if ($giftCard === null) {
            throw new GiftCardNotFoundException($giftCardId);
        }
        $orgIdString       = $giftCard
            ->getOrganizationId()
            ->toString();
        $canAccessGiftCard = $this
            ->userRoleChecker
            ->hasAccessToOrganizationAsRole($user, $orgIdString, Role::$allRoles);
        if (!$canAccessGiftCard) {
            throw new GiftCardNotFoundException($giftCardId);
        }
        $giftCard->refund($user);
        if ($giftCard->getTransactionId() !== null) {
            $this->refund($giftCard);
        }
        $this->entityManager->persist($giftCard);
        $this->entityManager->flush();
        return $response->withJson(Http::status(200, $giftCard), 200);
    }

    public function createGiftCard(Request $request, Response $response): Response
    {
        $giftCardSettingsId = $request->getAttribute("giftCardSettingsId");
        $giftCardSettings   = $this->getGiftCardSettings($giftCardSettingsId);
        if ($giftCardSettings === null) {
            return $response->withJson(Http::status(404, "settings not found"), 404);
        }
        try {
            $giftCardInput = GiftCardCreationInput::createFromArray($request->getParsedBody());
        } catch (Throwable $throwable) {
            return $response->withJson(Http::status(400, "invalid body"), 400);
        }
        $giftCard = $this->giftCardFactory->giftCard($giftCardSettings, $giftCardInput);
        $this->entityManager->persist($giftCard);
        $this->entityManager->flush();

        $client = new QueueSender();
        $client->sendMessage(
            [
                'notificationContent' => [
                    'objectId' => $giftCard->getId(),
                    'title'    => 'Gift Card Created',
                    'kind'     => 'gift_card',
                    'link'     => '/gifting-activations',
                    'cardId'   => $giftCard->getId(),
                    'orgId'    => $giftCard->getOrganizationId(),
                    'message'  => $giftCard->getProfile()->getFullName() . ' just bought a ' . $giftCard->formattedCurrency() . ' gift card'
                ]
            ],
            QueueUrls::NOTIFICATION
        );

        return $response->withJson(Http::status(201, $giftCard), 201);
    }

    public function activateGiftCard(Request $request, Response $response): Response
    {
        $giftCardId = $request->getAttribute('id');
        $token      = $request->getParam('token');
        if ($token === null) {
            return $response->withJson(Http::status(400, "no token supplied"), 400);
        }
        /** @var GiftCard | null $giftCard */
        $giftCard = $this
            ->entityManager
            ->getRepository(GiftCard::class)
            ->find($this->giftcardIdPrettyfier->unpretty($giftCardId));
        if ($giftCard === null) {
            return $response->withJson(Http::status(404, "gift-card not found"), 404);
        }
        $charge = $this->charge($giftCard, $token);
        $giftCard->activate($charge->id);
        $this->entityManager->persist($giftCard);
        $this->entityManager->flush();
        $this->sendEmail($giftCard);
        return $response->withJson(Http::status(200, $giftCard), 200);
    }

    /**
     * @param GiftCard $card
     * @param string|null $email
     */
    private function sendEmail(GiftCard $card, ?string $email = null): void
    {
        $recipients = [
            $card->emailSendTo()
        ];

        if ($email !== null) {
            $additional       = $card->emailSendTo();
            $additional['to'] = $email;
            $recipients[]     = [$additional];
        }

        foreach ($recipients as $recipient) {
            $this
                ->mailSender
                ->send(
                    $recipient,
                    $card->emailDetails(),
                    "GiftingTemplate",
                    $card->description()
                );
        }
    }

    public function reporting(Request $request, Response $response): Response
    {
        $now            = new DateTime();
        $organizationId = $request->getAttribute('orgId');
        $startDate      = (int)$request->getParam('startDate', $now->getTimestamp());
        $endDate        = (int)$request->getParam('endDate', $now->sub(new DateInterval('P30D'))->getTimestamp());
        $results        = $this
            ->giftcardReportRepository
            ->getReport(
                $organizationId,
                (new DateTime())->setTimestamp($startDate),
                (new DateTime())->setTimestamp($endDate)
            );

        $jsonRes = json_encode(Http::status(200, $results), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $response
            ->withHeader("Content-Type", "application/json;charset=utf-8")
            ->write($jsonRes);
    }

    public function fetchAllGiftCards(Request $request, Response $response): Response
    {
        $organizationId = $request->getAttribute('orgId');
        $limit          = (int)$request->getQueryParam('limit', 25);
        $offset         = (int)$request->getQueryParam('offset', 0);
        $queryBuilder   = $this
            ->entityManager
            ->createQueryBuilder();
        $expr           = $queryBuilder->expr();

        $query = $queryBuilder
            ->select('gc')
            ->from(GiftCard::class, 'gc')
            ->where($expr->eq('gc.organizationId', ':organizationId'))
            ->orderBy(new OrderBy('gc.createdAt', 'DESC'))
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('organizationId', $organizationId)
            ->getQuery();

        $resp = new PaginatedResponse($query);
        return $response->withJson($resp);
    }

    private function charge(GiftCard $card, string $token): Charge
    {
        $userId = $card
            ->getGiftCardSettings()
            ->getStripeConnect()
            ->getStripeUserId();
        Stripe::setAccountId($userId);
        return Charge::create(
            $card->stripeDetails($token),
        );
    }

    private function refund(GiftCard $card): Refund
    {
        $userId = $card
            ->getGiftCardSettings()
            ->getStripeConnect()
            ->getStripeUserId();

        Stripe::setAccountId($userId);

        return Refund::create(
            [
                'charge' => $card->getTransactionId(),
            ]
        );
    }
}
