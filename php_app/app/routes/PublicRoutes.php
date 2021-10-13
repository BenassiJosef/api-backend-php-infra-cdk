<?php

/**
 * Created by jamieaitken on 19/03/2018 at 10:49
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Billing\Quotes\_QuotesController;
use App\Controllers\Billing\Subscription;
use App\Controllers\Branding\_BrandingController;
use App\Controllers\Gifting\Gifting;
use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Marketing\Report\URLShortenerEventController;
use App\Controllers\Marketing\_MarketingLegacy;
use App\Controllers\Members\_MembersController;
use App\Controllers\Redirect\Redirect;
use App\Controllers\Stats\StatsController;
use App\Package\DataSources\InteractionController;
use App\Package\GiftCard\GiftCardController;
use App\Package\Marketing\MarketingController;
use App\Package\Menu\MenuController;
use App\Package\Reviews\Controller\ReviewsSettingsController;
use App\Package\Reviews\Controller\UserReviewController;
use App\Package\Service\ServiceRequestController;
use App\Package\Vendors\VendorsController;
use App\Package\WebForms\EmailSignupController;
use App\Package\WebForms\Settings;
use App\Policy\CookieMiddleware;
use App\Utils\Validators\Email;

$app->group(
	'/public',
	function () {
		$this->group('/menus', function () {
			$this->get('', MenuController::class . ':getMenuSitemap');
			$this->get('/{id}', MenuController::class . ':getMenuItem');
		});
		$this->get('/closest-locations', LocationPositionController::class . ':getClosestLocations');
		$this->get('/vendors', VendorsController::class . ':getVendors');
		$this->get('/redirect', Redirect::class . ':doRedirect')->add(CookieMiddleware::class);
		$this->get('/interactions/{interactionId}/end', InteractionController::class . ':endInteraction');
		$this->post('/opt-service', MarketingController::class . ':optUsingServiceEmail');
		$this->group(
			'/organizations/{orgId}',
			function () {
				$this->get('/valid-subscription', Subscription::class . ':hasValidSubscriptionRoute');
				$this->post('/email-registration', EmailSignupController::class . ':registerEmail');
				$this->post('/opt', MarketingController::class . ':optUsingEmail');
			}
		);
		$this->get('/form/{id}', Settings::class . ':getFormRequest');
		$this->get('/gifting-sitemap', Gifting::class . ':getPublicSitemapRoute');

		$this->group(
			'/gifting',
			function () {
				$this->group(
					'/{giftCardSettingsId}',
					function () {
						$this->get('', Gifting::class . ':getPublicRoute');
						$this->post('', GiftCardController::class . ':createGiftCard');
					}
				);
				$this->post('/cards/{id}/activate', GiftCardController::class . ':activateGiftCard');
			}
		);
		$this->group(
			'/opt_out',
			function () {
				$this->get('/email', _MarketingLegacy::class . ':optOutEmailRoute');
				$this->post('/sms', _MarketingLegacy::class . ':optOutSMSRoute');
				$this->get('/from-campaign', MarketingController::class . ':optOut');
			}
		);
		$this->group(
			'/quote',
			function () {
				$this->get('', _QuotesController::class . ':getsRoute');
				$this->get('/{id}', _QuotesController::class . ':publicGetRoute');
			}
		);
		$this->get(
			'/export/{filename}',
			_UploadStorageController::class . ':exportCSVRoute'
		);
		$this->get('/branding', _BrandingController::class . ':getMetaDataRoute');
		$this->get(
			'/track/{shortUrl}',
			URLShortenerEventController::class . ':createRoute'
		);

		$this->group(
			'/review',
			function () {
				$this->post(
					'/{id}',
					UserReviewController::class . ':createReview'
				);
				$this->get('/{id}', ReviewsSettingsController::class . ':getReviewSettings');
				$this->get('/test/migrate', ReviewsSettingsController::class . ':migrate');
			}
		);
		$this->group(
			'/validation',
			function () {
				$this->post('/email', Email::class . ':isValidRoute');
				$this->get('/email', Email::class . ':isValidGetRoute');
			}
		);
		$this->post('/signup', _MembersController::class . ':createTrialFromFormRoute');
		$this->post('/create-account', _MembersController::class . ':createAccountRoute');
		$this->get('/stats', StatsController::class . ':getRoute');
	}
);
