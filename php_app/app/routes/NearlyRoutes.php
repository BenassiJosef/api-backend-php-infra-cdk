<?php

/**
 * Created by jamieaitken on 19/03/2018 at 12:34
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Mikrotik\MikrotikSymlinkController;
use App\Controllers\Integrations\UniFi\_UniFiController;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Controllers\Nearly\_NearlyDevicesController;
use App\Controllers\Nearly\Validations\EmailValidator;
use App\Controllers\Nearly\NearlyAuthController;
use App\Controllers\Nearly\NearlyPayPalController;
use App\Controllers\Nearly\NearlyProfile\NearlyOptOut;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileAccountService;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileDownloadController;
use App\Controllers\Nearly\NearlyProfileController;
use App\Controllers\Nearly\Stories\NearlyStoryTrackingController;
use App\Controllers\Registrations\_RegistrationsController;
use App\Package\AppleSignIn\AppleSignIn;
use App\Package\Nearly\NearlyAuthentication;
use App\Package\Nearly\NearlyController;
use App\Package\Vendors\InformController;

$app->group(
	'/nearly',
	function () {
		$this->get('/inform/{serial}', InformController::class . ':getInform');
		$this->post('/apple', AppleSignIn::class . ':postTokenPath');
		$this->post('/auth', NearlyAuthentication::class . ':postAuthentication');
		$this->group(
			'/devices',
			function () {
				$this->get(
					'/usage/{profileId}/{serial}/{limit}',
					_NearlyDevicesController::class . ':checkDataUsageRoute'
				);
				$this->get('/verify', App\Controllers\Nearly\_NearlyDevicesController::class . ':verifyUserRoute');
				$this->get('', _NearlyDevicesController::class . ':getPaidDevicesRoute');
				$this->delete('/{mac}', _NearlyDevicesController::class . ':updatePaidDevicesRoute');
				$this->get('/ap/{mac}', _NearlyDevicesController::class . ':getAp');
			}
		);

		$this->group(
			'/profile/{id}',
			function () {
				$this->group(
					'/info',
					function () {
						$this->get(
							'',
							NearlyProfileController::class . ':loadProfileRoute'
						);
						$this->group(
							'/organizations',
							function () {
								$this->get('', NearlyProfileController::class . ':loadOrganisationsRoute');
								$this->put('', NearlyProfileController::class . ':organisationOptOut');
							}
						);
						$this->get(
							'/download/{type}',
							NearlyProfileDownloadController::class . ':createRoute'
						);
						$this->post(
							'',
							NearlyOptOut::class . ':optOutRoute'
						);
						$this->delete('', NearlyProfileController::class . ':deleteAccountRoute');
						$this->put(
							'/password',
							NearlyProfileAccountService::class . ':updatePasswordRoute'
						);
					}
				);
				$this->post('', NearlyProfileAccountService::class . ':createRoute');
				$this->get(
					'',
					NearlyProfileAccountService::class . ':hasAccountRoute'
				);
				$this->put(
					'',
					NearlyProfileAccountService::class . ':resetPasswordRoute'
				);
			}
		)->add(App\Policy\NearlyLogInService::class);

		$this->get('/settings', NearlyController::class . ':getSettings');
		$this->get(
			'/landing/{serial}',
			_LocationSettingsController::class . ':getLandingPageRoute'
		);
		$this->put(
			'/registrations/{serial}',
			_RegistrationsController::class . ':updateRoute'
		);
		$this->put('/registrations', _RegistrationsController::class . ':updateNearlyUserRoute');
		$this->group(
			'/unifi',
			function () {
				$this->get('/{id}', _UniFiController::class . ':getSiteRoute');
				$this->post('', _UniFiController::class . ':authRoute');
			}
		);
		$this->group(
			'/radius',
			function () {
				$this->get(
					'/secret/{serial}',
					App\Controllers\Integrations\Radius\_RadiusController::class . ':getSecretRoute'
				);
			}
		);
		$this->group(
			'/mikrotik',
			function () {
				$this->get(
					'/{serial}',
					MikrotikSymlinkController::class . ':getVirtualSerialAssociatedWithPhysicalSerialRoute'
				);
			}
		);
		$this->group(
			'/validation',
			function () {
				$this->get('/email/{serial}', EmailValidator::class . ':emailValidatorRoute');
			}
		);
		$this->group(
			'/paypal',
			function () {
				$this->post('', NearlyPayPalController::class . ':createRoute');
				$this->get('', NearlyPayPalController::class . ':redirectRoute');
				$this->post('/confirm', NearlyPayPalController::class . ':confirmRoute');
			}
		);
		$this->group(
			'/stories',
			function () {
				$this->get(
					'/{trackingId}/{action}',
					NearlyStoryTrackingController::class . ':trackRoute'
				);
			}
		);
	}
);
