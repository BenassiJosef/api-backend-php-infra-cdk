<?php

namespace App\Controllers\Gifting;

use App\Models\Billing\Organisation\Subscriptions;
use App\Models\GiftCardSettings;
use App\Models\Role;
use App\Models\StripeConnect;
use App\Package\Exceptions\BaseException;
use App\Package\Organisations\UserRoleChecker;
use App\Package\PrettyIds\HumanReadable;
use App\Package\PrettyIds\IDPrettyfier;
use App\Package\PrettyIds\URL;
use App\Package\RequestUser\UserProvider;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use App\Package\Organisations\OrganizationService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Throwable;

class Gifting
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @var OrganizationService
	 */
	private $organizationService;

	/**
	 * @var IDPrettyfier $settingsIdPrettyfier
	 */
	private $settingsIdPrettyfier;

	/**
	 * @var IDPrettyfier $giftcardIdPrettyfier
	 */
	private $giftcardIdPrettyfier;

	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;

	/**
	 * @var UserProvider $userProvider
	 */
	private $userProvider;

	public function __construct(
		OrganizationService $organizationService,
		EntityManager $em,
		UserRoleChecker $userRoleChecker,
		UserProvider $userProvider
	) {
		$this->organizationService  = $organizationService;
		$this->entityManager        = $em;
		$this->settingsIdPrettyfier = new URL();
		$this->giftcardIdPrettyfier = new HumanReadable();
		$this->userRoleChecker      = $userRoleChecker;
		$this->userProvider         = $userProvider;
	}

	public function getPublicSitemapRoute(Request $request, Response $response): Response
	{

		$settings = $this
			->entityManager
			->getRepository(GiftCardSettings::class)
			->findAll();
		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTING_FOUND');
			return $response->withJson($res, $res['status']);
		}
		$arrResponse = [];
		foreach ($settings as $item) {
			$arrResponse[] = ['id' => $item->getId()];
		}

		$res = Http::status(200, $arrResponse);
		return $response->withJson($res, $res['status']);
	}


	public function getPublicRoute(Request $request, Response $response): Response
	{
		$id       = $request->getAttribute('giftCardSettingsId', null);

		if (is_null($id)) {
			throw new BaseException('NO_ID_FOUND', StatusCode::HTTP_BAD_REQUEST);
		}
		try {
			$parsedId = $this->settingsIdPrettyfier->unpretty($id);
		} catch (Throwable $e) {
			throw new BaseException('ID_INVALID', StatusCode::HTTP_BAD_REQUEST);
		}

		$settings = $this
			->entityManager
			->getRepository(GiftCardSettings::class)
			->findOneBy(
				[
					'id' => $parsedId,
				]
			);
		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTING_FOUND');
			return $response->withJson($res, $res['status']);
		}
		$res = Http::status(200, $settings->jsonSerialize());
		return $response->withJson($res, $res['status']);
	}


	public function getAllGiftingSettings(Request $request, Response $response): Response
	{
		$orgId    = $request->getAttribute("orgId");
		$settings = $this
			->entityManager
			->getRepository(GiftCardSettings::class)
			->findBy(
				[
					'organizationId' => $orgId
				]
			);

		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTINGS_FOUND');
			return $response->withJson($res, $res['status']);
		}

		$arrResponse = [];
		foreach ($settings as $item) {
			$arrResponse[] = $item->jsonSerialize();
		}

		$res = Http::status(200, $arrResponse);
		return $response->withJson($res, $res['status']);
	}

	public function getGiftingSettings(Request $request, Response $response): Response
	{
		$orgId    = $request->getAttribute("orgId");
		$id       = $request->getAttribute("id");
		$settings = $this
			->entityManager
			->getRepository(GiftCardSettings::class)
			->findOneBy(
				[
					'organizationId' => $orgId,
					'id'             => $this->settingsIdPrettyfier->unpretty($id),
				]
			);

		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTING_FOUND');
			return $response->withJson($res, $res['status']);
		}

		$res = Http::status(200, $settings->jsonSerialize());
		return $response->withJson($res, $res['status']);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws Exception
	 */
	public function createGiftingSettings(Request $request, Response $response): Response
	{
		$orgId        = $request->getAttribute("orgId");
		$user         = $this->userProvider->getOauthUser($request);
		$canAccessOrg = $this
			->userRoleChecker
			->hasAccessToOrganizationAsRole($user, $orgId, Role::$allRoles);

		if (!$canAccessOrg) {
			return $response->withJson(Http::status(403, "cannot access org"), 403);
		}

		$body = $request->getParsedBody();
		/** @var StripeConnect $stripeConnect */
		$stripeConnect = $this
			->entityManager
			->getRepository(StripeConnect::class)
			->findOneBy(
				[
					"id" => $body['stripeConnectId'],
				]
			);

		/** @var Subscriptions | null $organizationSubscription */
		$organizationSubscription = $this
			->entityManager
			->getRepository(Subscriptions::class)
			->findOneBy(
				['organizationId' => $orgId]
			);

		$currency = !empty($body['currency']) ? $body['currency'] : "GBP";
		if ($organizationSubscription !== null) {
			$currency = $organizationSubscription->getCurrency();
		}
		$serial = !empty($body['serial']) ? $body['serial'] : null;

		if (empty($stripeConnect)) {
			$res = Http::status(403, 'STRIPE_INVALID');
			return $response->withJson($res, $res['status']);
		}

		$settings = new GiftCardSettings(
			$stripeConnect,
			$body['title'],
			$body['description'],
			$body['image'],
			$currency,
			$serial
		);

		if (!empty($body['backgroundImage'])) {
			$settings->setBackgroundImage($body['backgroundImage']);
		}
		if (!empty($body['colour'])) {
			$settings->setColour($body['colour']);
		}

		$this->entityManager->persist($settings);
		$this->entityManager->flush();

		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTINGS_FOUND');
			return $response->withJson($res, $res['status']);
		}

		$res = Http::status(200, $settings->jsonSerialize());
		return $response->withJson($res, $res['status']);
	}

	public function updateGiftingSettings(Request $request, Response $response): Response
	{
		$orgId = $request->getAttribute("orgId");
		$id    = $request->getAttribute("id");
		$body  = $request->getParsedBody();

		/** @var GiftCardSettings $settings */
		$settings = $this->entityManager->getRepository(GiftCardSettings::class)->findOneBy(
			[
				'organizationId' => $orgId,
				'id'             => $this->settingsIdPrettyfier->unpretty($id),
			]
		);

		if (empty($settings)) {
			$res = Http::status(403, 'NO_SETTING_FOUND');
			return $response->withJson($res, $res['status']);
		}

		$serial = !empty($body['serial']) ? $body['serial'] : null;
		$settings->setSerial($serial);
		if (!empty($body['title'])) {
			$settings->setTitle($body['title']);
		}
		if (!empty($body['description'])) {
			$settings->setDescription($body['description']);
		}
		if (!empty($body['image'])) {
			$settings->setImage($body['image']);
		}
		if (!empty($body['backgroundImage'])) {
			$settings->setBackgroundImage($body['backgroundImage']);
		}
		if (!empty($body['colour'])) {
			$settings->setColour($body['colour']);
		}
		if (!empty($body['stripeConnectId'])) {
			$settings->setStripeConnectId($body['stripeConnectId']);
		}

		$this->entityManager->persist($settings);
		$this->entityManager->flush();

		$res = Http::status(200, $settings->jsonSerialize());
		return $response->withJson($res, $res['status']);
	}
}
