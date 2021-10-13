<?php

/**
 * Created by jamieaitken on 19/03/2018 at 11:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Marketing\Campaign\CampaignsController;
use App\Controllers\Marketing\Campaign\URLShortener\URLShortenerController;
use App\Controllers\Marketing\Campaign\_PreviewCampaignController;
use App\Controllers\Marketing\Report\URLShortenerEventController;
use App\Controllers\Marketing\Report\_MarketingReportController;
use App\Controllers\Marketing\_MarketingCallBackController;
use App\Controllers\Marketing\_MarketingLegacy;
use App\Package\Marketing\MarketingBounces;
use App\Package\Marketing\MarketingController;

$app->group(
	'/marketing',
	function () {

		$this->post('/email-bounce', MarketingBounces::class . ':postBounce')
			->add(App\Policy\Auth::class);

		$this->post(
			'/callback/sms',
			_MarketingCallBackController::class . ':insertSmsCallBackRoute'
		);
		$this->post('/callback/email', _MarketingCallBackController::class . ':insertEmailCallBackRoute');
		$this->post('/callback/sesemail', _MarketingCallBackController::class . ':sesCallback');


		$this->group('/{orgId}', function () {
			$this->get('/opt-out', MarketingController::class . ':getOptOuts');
			$this->get('/overview', _MarketingReportController::class . ':overviewRoute');
			$this->post('/audience', _MarketingLegacy::class . ':marketingGuessCheckerRoute');
			$this->get('/report', MarketingController::class . ':getOrganisationCampaignReport');
			$this->group(
				'/urls',
				function () {
					$this->group(
						'/reporting',
						function () {
							$this->get(
								'/{shortUrl}',
								URLShortenerEventController::class . ':getAnalyticsRoute'
							);
							$this->get(
								'',
								URLShortenerEventController::class . ':getAllShortLinksRoute'
							);
						}
					);
					$this->post(
						'',
						URLShortenerController::class . ':createRoute'
					);
					$this->get(
						'',
						URLShortenerController::class . ':getRoute'
					);
				}
			);

			$this->group(
				'/campaigns',
				function () {
					$this->get('', MarketingController::class . ':fetchAllCampaigns');
					$this->get(
						'/reports/users',
						_MarketingReportController::class . ':loadCampaignInfoRoute'
					);
					$this->post('', _MarketingLegacy::class . ':saveCampaignRoute');
					$this->post('/send', CampaignsController::class . ':sendRoute');
					$this->group('/{id}', function () {
						$this->get('/report', MarketingController::class . ':getCampaignReport');
						$this->get('/report/{event}', MarketingController::class . ':getCampaignEventReport');
						$this->get('', _MarketingLegacy::class . ':getCampaignRoute');
						$this->put('', _MarketingLegacy::class . ':saveCampaignRoute');
						$this->delete('', _MarketingLegacy::class . ':deleteCampaignRoute');
					});
				}
			);

			$this->group(
				'/messages',
				function () {
					$this->get('', MarketingController::class . ':fetchAllMessages');
					$this->get('/{id}', _MarketingLegacy::class . ':getMessageRoute');
					$this->delete('/{id}', MarketingController::class . ':deleteMessage');
					$this->put('/{id}', _MarketingLegacy::class . ':saveMessageRoute');
					$this->post('', _MarketingLegacy::class . ':saveMessageRoute');
					$this->post(
						'/preview',
						_PreviewCampaignController::class . ':sendTestMessageRoute'
					);
				}
			);

			$this->group(
				'/events',
				function () {
					$this->get('', _MarketingLegacy::class . ':getEventsRoute');
					$this->get('/{id}', _MarketingLegacy::class . ':getEventRoute');
					$this->put('/{id}', _MarketingLegacy::class . ':saveEventRoute');
					$this->post('', _MarketingLegacy::class . ':saveEventRoute');
				}
			);
		})->add(App\Policy\Auth::class);
	}
);
