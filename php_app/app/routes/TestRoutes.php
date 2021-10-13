<?php

/**
 * Created by jamieaitken on 19/03/2018 at 10:52
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Locations\Settings\_LocationScheduleController;
use App\Controllers\Marketing\_MarketingRunner;
use App\Models\Role;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\LegacyCompatibilityMiddleware;
use App\Package\Billing\SMSTransactions;
use App\Policy\Auth;
use App\Utils\PushNotifications;

$app->group(
	'/test',
	function () {
		//$this->post('', Integrations\Mikrotik\_MikrotikInformController::class . ':testMailRoute');
		//$this->get('', Integrations\HubSpot\_HubSpotContact::class . ':getContactRoute');
		// $this->post('/{serial}', Integrations\UniFi\_UniFiController::class . ':setup');
		//$this->post('/{serial}', \App\Controllers\Locations\_LocationCreationController::class . ':locationScheduleRoute');
		//$this->get('/{serial}', \App\Controllers\Locations\Debugger::class . ':hasFacebookPageIdRoute');
		//$this->get('/{serial}',\App\Controllers\Locations\Reports\PredictiveReports\PredictConnectionsReportController::class . ':getRoute');
		$this->post('', PushNotifications::class . ':testRoute');
		$this->get('', PushNotifications::class . ':getIpRoute')
			->add(LegacyCompatibilityMiddleware::class)->add(AuthMiddleware::class);
		$this->post(
			'/{serial}',
			_LocationScheduleController::class . ':getNearestTimezoneRoute'
		);
		$this->get(
			'/{campaignId}',
			_MarketingRunner::class . ':testGetAudienceRoute'
		);
		$this->put(
			'/credits',
			SMSTransactions::class . ':testRoute'
		);
		$this->get('/mail/{template}', _MailController::class . ':testRoute');
		$this->get('/email/test', _MailController::class . ':test');
	}
);
