<?php

/**
 * Created by jamieaitken on 19/03/2018 at 12:21
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Billing\Subscriptions\FailedTransactionController;
use App\Controllers\Integrations\Google\PlaceIDVerifierController;
use App\Controllers\Integrations\Mikrotik\MikrotikSymlinkController;
use App\Controllers\Integrations\TripAdvisor\TripAdvisorReviewController;
use App\Controllers\Locations\_LocationsController;
use App\Controllers\Locations\Debugger;
use App\Controllers\Locations\Devices\_ConnectedController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Controllers\Locations\Devices\_WhitelistController;
use App\Controllers\Locations\Info\LocationsInfoController;
use App\Controllers\Locations\MenuGenerator\MenuGenerator;
use App\Controllers\Locations\Pricing\_LocationPaymentMethodController;
use App\Controllers\Locations\Pricing\_LocationPlanController;
use App\Controllers\Locations\Reports\_LocationReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\MultiSiteReportController;
use App\Controllers\Locations\Reports\Overview\OverviewController;
use App\Controllers\Locations\Reviews\LocationReviewController;
use App\Controllers\Locations\Reviews\LocationReviewErrorController;
use App\Controllers\Locations\Reviews\LocationReviewTimelineController;
use App\Controllers\Locations\Settings\_LocationScheduleController;
use App\Controllers\Locations\Settings\Bandwidth\_BandwidthController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Capture\_CaptureController;
use App\Controllers\Locations\Settings\General\_GeneralController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\Social\LocationFacebookController;
use App\Controllers\Locations\Settings\Templating\LocationTemplateController;
use App\Controllers\Locations\Settings\Timeouts\_TimeoutsController;
use App\Controllers\Locations\Settings\Type\LocationTypeController;
use App\Controllers\Locations\Settings\Type\LocationTypeSerialController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Controllers\Locations\Super\_SuperController;
use App\Controllers\Members\_MembersController;
use App\Controllers\Nearly\Stories\NearlyStoryController;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\LegacyCompatibilityMiddleware;
use App\Package\Location\LocationController;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\OrgTypeRoleConfigurationMiddleware;
use App\Package\Organisations\ResourceRoleConfigurationMiddleware;
use App\Package\Organisations\ResourceRoleMiddleware;
use App\Package\Vendors\VendorsController;

$app->group(
	'/locations',
	function () {

		$this->group(
			'/super',
			function () {
				$this->post('/command', _SuperController::class . ':bulkUpdate');
			}
		)->add(OrgTypeMiddleware::class)
			->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]));

		$this->get(
			'/search/{uid}/{context}',
			_LocationsController::class . ':fetchLocationsThatUserHasAccessToRoute'
		)
			->add(LegacyCompatibilityMiddleware::class)
			->add(AuthMiddleware::class);


		$this->get(
			'/insights/{reportKind}',
			MultiSiteReportController::class . ':getRoute'
		);


		$this->group(
			'/type',
			function () {
				$this->post(
					'/create',
					LocationTypeController::class . ':createRoute'
				)->add(OrgTypeMiddleware::class)
					->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]));
				$this->get('/list', LocationTypeController::class . ':getRoute');
			}
		);

		$this->group(
			'/errors',
			function () {
				$this->group(
					'/reviews',
					function () {
						$this->get(
							'',
							LocationReviewErrorController::class . ':getAllRoute'
						);
						$this->get(
							'/{id}',
							LocationReviewErrorController::class . ':getRoute'
						);
					}
				);
				$this->group(
					'/payments',
					function () {
						$this->get(
							'',
							FailedTransactionController::class . ':getAllFailingRoute'
						);
						$this->get(
							'/{uid}',
							FailedTransactionController::class . ':getAllFailingByCustomerRoute'
						);
					}
				);
			}
		)->add(OrgTypeMiddleware::class)
			->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]));

		$this->group(
			'/{serial}',
			function () {

				$this->group(
					'/paid',
					function () {
						$this->get('', FailedTransactionController::class . ':getRoute');
						$this->post(
							'',
							FailedTransactionController::class . ':createManualRoute'
						);
						$this->delete(
							'',
							FailedTransactionController::class . ':deleteManualRoute'
						);
					}
				)->add(OrgTypeMiddleware::class)
					->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]));

				$this->group(
					'/reviews',
					function () {
						$this->group(
							'/tripadvisor',
							function () {
								$this->post(
									'',
									TripAdvisorReviewController::class . ':createRoute'
								);
								$this->get(
									'',
									TripAdvisorReviewController::class . ':getRoute'
								);
							}
						);
						$this->group(
							'/ratings',
							function () {
								$this->get(
									'',
									LocationReviewTimelineController::class . ':getRoute'
								);
							}
						);
						$this->group(
							'/types',
							function () {
								$this->post(
									'',
									LocationReviewController::class . ':createReviewTypeRoute'
								);
								$this->get(
									'',
									LocationReviewController::class . ':getReviewTypesRoute'
								);
							}
						);
					}
				);

				$this->group(
					'/payments',
					function () {
						$this->group(
							'/plans',
							function () {
								$this->group(
									'/{planId}',
									function () {
										$this->get(
											'',
											_LocationPlanController::class . ':receivePlanRoute'
										);
										$this->delete(
											'',
											_LocationPlanController::class . ':deletePlanRoute'
										);
										$this->put(
											'',
											_LocationPlanController::class . ':updatePlanRoute'
										);
									}
								);
								$this->get(
									'',
									_LocationPlanController::class . ':receiveAllPlansForSerialRoute'
								);
								$this->post(
									'',
									_LocationPlanController::class . ':createNewPlanRoute'
								);
							}
						);
						$this->group(
							'/method',
							function () {
								$this->put(
									'',
									_LocationPaymentMethodController::class . ':updateMethodRoute'
								);
								$this->get(
									'',
									_LocationPaymentMethodController::class . ':getMethodsRoute'
								);
							}
						);
					}
				)->add(ResourceRoleMiddleware::class)
					->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller, Role::LegacyModerator]));

				$this->group(
					'/menu',
					function () {
						$this->get('', MenuGenerator::class . ':requestMenuRoute');
					}
				);

				$this->group(
					'/insight',
					function () {
						$this->get(
							'/{kind}/{start}/{end}',
							_LocationReportController::class .
								':generateReportRoute'
						);
					}
				)->add(ResourceRoleMiddleware::class)
					->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller, Role::LegacyModerator, Role::LegacyMarketeer]));

				$this->group(
					'/settings',
					function () {
						$this->get('', LocationController::class . ':get');
						$this->group(
							'/general',
							function () {
								$this->get('', _GeneralController::class . ':getRoute');
								$this->put('', _GeneralController::class . ':updateRoute');
							}
						);

						$this->group(
							'/capture',
							function () {
								$this->get('', _CaptureController::class . ':getRoute');
								$this->put('', _CaptureController::class . ':updateRoute');
							}
						);

						$this->post(
							'/super',
							_SuperController::class . ':postRoute'
						)->add(OrgTypeMiddleware::class)
							->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]));

						$this->group(
							'/branding',
							function () {
								$this->post(
									'',
									BrandingController::class . ':createFromTemplateRoute'
								);
								$this->get('', BrandingController::class . ':getRoute');
								$this->put('', BrandingController::class . ':updateRoute');
								$this->delete(
									'/{type}',
									BrandingController::class . ':deleteRoute'
								);
								$this->get(
									'/palette',
									BrandingController::class . ':getColorsRoute'
								);
							}
						);

						$this->group(
							'/type',
							function () {
								$this->group(
									'/{locationType}',
									function () {
										$this->post(
											'',
											LocationTypeSerialController::class . ':createRoute'
										);
										$this->put(
											'',
											LocationTypeSerialController::class . ':updateRoute'
										);
										$this->delete(
											'',
											LocationTypeSerialController::class . ':deleteRoute'
										);
									}
								);
								$this->get(
									'',
									LocationTypeSerialController::class . ':getRoute'
								);
							}
						);

						$this->group(
							'/schedule',
							function () {
								$this->post(
									'',
									_LocationScheduleController::class . ':createOrUpdateRoute'
								);
								$this->get('', _LocationScheduleController::class . ':getRoute');
							}
						)->add(ResourceRoleMiddleware::class)
							->add(
								new ResourceRoleConfigurationMiddleware(
									[
										Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller,
										Role::LegacyModerator
									]
								)
							);

						$this->group(
							'/location',
							function () {
								$this->put(
									'',
									LocationPositionController::class . ':updateRoute'
								);
								$this->get(
									'',
									LocationPositionController::class . ':getRoute'
								);
								$this->group(
									'/{placeId}',
									function () {
										$this->get(
											'',
											PlaceIDVerifierController::class . ':getRoute'
										);
									}
								);
							}
						);

						$this->group(
							'/wifi',
							function () {
								$this->put('/vendor', VendorsController::class . ':changeVendor');
								$this->get('', _WiFiController::class . ':getRoute');
								$this->put('', _WiFiController::class . ':updateRoute');
							}
						);

						$this->group(
							'/bandwidth',
							function () {
								$this->get('', _BandwidthController::class . ':getRoute');
								$this->put(
									'',
									_BandwidthController::class . ':updateRoute'
								);
							}
						);

						$this->group(
							'/timeouts',
							function () {
								$this->get(
									'',
									_TimeoutsController::class . ':getTimeoutsRoute'
								);
								$this->put(
									'',
									_TimeoutsController::class . ':updateRoute'
								);
							}
						);

						$this->group(
							'/other',
							function () {
								$this->get('', LocationOtherController::class . ':getRoute');
								$this->put(
									'',
									LocationOtherController::class . ':updateRoute'
								);
							}
						);

						$this->group(
							'/social',
							function () {
								$this->get(
									'',
									LocationFacebookController::class . ':getRoute'
								);
								$this->put(
									'',
									LocationFacebookController::class . ':updateRoute'
								);
							}
						);


						$this->group(
							'/template',
							function () {
								$this->post(
									'',
									LocationTemplateController::class . ':createTemplateRoute'
								);
								$this->get(
									'',
									LocationTemplateController::class . ':getTemplateRoute'
								);
								$this->put(
									'',
									LocationTemplateController::class . ':updateTemplateRoute'
								);
								$this->delete(
									'',
									LocationTemplateController::class . ':restoreDefaultRoute'
								);
							}
						);
					}
				)->add(ResourceRoleMiddleware::class)
					->add(
						new ResourceRoleConfigurationMiddleware(
							[
								Role::LegacyAdmin,
								Role::LegacySuperAdmin,
								Role::LegacyReseller,
								Role::LegacyModerator
							]
						)
					);

				$this->group(
					'/device',
					function () {
						$this->group(
							'/{id}',
							function () {
								$this->put(
									'',
									_LocationsDevicesController::class . ':updateDeviceRoute'
								);
								$this->delete(
									'',
									_LocationsDevicesController::class . ':deleteDeviceRoute'
								);
							}
						);
						$this->group(
							'/whitelist',
							function () {
								$this->get('', _WhitelistController::class . ':getRoute');
								$this->post('', _WhitelistController::class . ':postRoute');
								$this->put('/{id}', _WhitelistController::class . ':updateRoute');
								$this->delete('/{id}', _WhitelistController::class . ':deleteRoute');
							}
						);
						$this->group(
							'/connected',
							function () {
								$this->get('', _ConnectedController::class . ':getRoute');
							}
						);
						$this->post('', _LocationsDevicesController::class . ':postDeviceRoute');
						$this->get('', _LocationsDevicesController::class . ':getDevicesRoute');
					}
				)->add(ResourceRoleMiddleware::class)
					->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller, Role::LegacyModerator]));

				$this->group(
					'/team',
					function () {
						$this->put('', _MembersController::class . ':updateMemberAccessRoute');
						$this->get('', _MembersController::class . ':getMemberAccessRoute');
					}
				)->add(ResourceRoleMiddleware::class)
					->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller, Role::LegacyModerator]));

				$this->group(
					'/debugger',
					function () {
						$this->get('/connections', Debugger::class . ':hasConnectionsRoute');
						$this->get('/inform', Debugger::class . ':hasInformRoute');
						$this->get('/config', Debugger::class . ':canReceiveConfigRoute');
						$this->get('/setup', Debugger::class . ':hasBeenSetupRoute');
						$this->get('/facebook', Debugger::class . ':hasFacebookPageIdRoute');
					}
				);

				$this->get('/about', LocationsInfoController::class . ':getRoute');
				$this->delete(
					'',
					_LocationsController::class . ':delete'
				)->add(ResourceRoleMiddleware::class)
					->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller, Role::LegacyModerator]));
				$this->get('/questions', _LocationsController::class . ':getLocationQuestionsRoute');


				$this->group(
					'/symlink',
					function () {
						$this->get(
							'',
							MikrotikSymlinkController::class . ':getVirtualSerialAssociatedWithPhysicalSerialRoute'
						);
						$this->put(
							'',
							MikrotikSymlinkController::class . ':updateThePhysicalSerialAssociatedWithVirtualSerialRoute'
						);
					}
				);

				$this->group(
					'/nearly',
					function () {
						$this->group(
							'/stories',
							function () {
								$this->get('', NearlyStoryController::class . ':getRoute');
								$this->post('', NearlyStoryController::class . ':createOrUpdateRoute');
								$this->delete('/{pageId}/{status}', NearlyStoryController::class . ':archivePageRoute');
							}
						);
					}
				);
			}
		)
			->add(App\Policy\GetSerialAdmin::class)
			->add(App\Policy\inAccess::class);
	}
)->add(LegacyCompatibilityMiddleware::class)->add(AuthMiddleware::class);
