<?php

declare(strict_types=1);

use App\Controllers\Billing\Subscription;
use App\Controllers\Billing\Subscriptions\_SubscriptionPlanController;
use App\Controllers\Filtering\FilterListController;
use App\Controllers\Gifting\Gifting;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Locations\Reports\Overview\OverviewController;
use App\Controllers\Locations\Settings\Policy\LocationPolicyController;
use App\Controllers\Marketing\Gallery\_GalleryController;
use App\Controllers\Marketing\Template\_MarketingUserGroupController;
use App\Controllers\Members\CustomerPricingController;
use App\Controllers\Organizations\OrganizationsController;
use App\Controllers\User\UserOverviewController;
use App\Controllers\WebTracker\WebTrackingController;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Auth\Access\Config\OrgTypeRoleConfig;
use App\Package\Auth\Access\Config\RoleConfig;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\LegacyCompatibilityMiddleware;
use App\Package\GiftCard\GiftCardController;
use App\Package\Interactions\InteractionsController;
use App\Package\Loyalty\Presentation\OrganizationStampCardController;
use App\Package\Marketing\MarketingController;
use App\Package\Menu\MenuController;
use App\Package\Organisations\Children\ChildController;
use App\Package\Organisations\OrganisationProgress;
use App\Package\Organisations\OrganizationSettingsController;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Reports\Origin\OriginReportController;
use App\Package\Reports\ReportsController;
use App\Package\Reviews\Controller\ReviewsReportController;
use App\Package\Reviews\Controller\ReviewsSettingsController;
use App\Package\Reviews\Controller\UserReviewController;
use App\Package\Segments\Controller\SegmentController;
use App\Package\Segments\Controller\SegmentMarketingController;
use App\Package\Upload\UploadController;
use App\Package\WebForms\Settings;

// Gifting routes added here, because why not
$app->group(
	'/gifting/cards/{id}',
	function () {
		$this->post('/redeem', GiftCardController::class . ':redeemGiftCard');
		$this->post('/refund', GiftCardController::class . ':refundGiftCard');
	}
)->add(AuthMiddleware::class);

$app->group(
	'/organisations',
	function () {
		$this->group(
			'/allusers',
			function () {
				$this->get('', OrganizationsController::class . ':getAllUsersRoute');
			}
		);
		$this->post('/trial', Subscription::class . ':postTrialSubscription');
		$this->group(
			'/{orgId}',
			function () {
				$this->get("/child", ChildController::class . ':children');
				$this->group(
					'/reviews',
					function () {
						$this->post(
							'/profile/{profileId}',
							ReviewsSettingsController::class . ':sendDelayedReviewsEmail'
						);
						$this->get('/reports', ReviewsReportController::class . ':getOverview');
						$this->get('/sentiment', ReviewsReportController::class . ':getKeywords');
						$this->group(
							'/responses',
							function () {
								$this->get('', UserReviewController::class . ':getReviews');
								$this->put('/{review_id}', UserReviewController::class . ':updateReview');
							}
						);
						$this->group(
							'/settings',
							function () {
								$this->group(
									'/{id}',
									function () {
										$this->post('/preview', ReviewsSettingsController::class . ':sendPreview');
										$this->get('', ReviewsSettingsController::class . ':getReviewSettings');
										$this->put('', ReviewsSettingsController::class . ':updateReviewSettings');
										$this->delete('', ReviewsSettingsController::class . ':deleteReviewSettings');
										$this->post(
											'/profile/{profileId}/send',
											ReviewsSettingsController::class . ':sendReviewEmail'
										);
									}
								);

								$this->get('', ReviewsSettingsController::class . ':getAllReviewSettings');

								$this->post('', ReviewsSettingsController::class . ':createReviewSettings');
							}
						);
					}
				);

				$this->group(
					'/segments',
					function () {
						$this->post('/preview', SegmentController::class . ':preview');
						$this->post('/dql', SegmentController::class . ':dql');
						$this->post('/reach', SegmentController::class . ':reach');
						$this->get('/metadata', SegmentController::class . ':metadata');
						$this->get('', SegmentController::class . ':fetchAll');
						$this->post('', SegmentController::class . ':create');

						$this->group(
							'/{id}',
							function () {
								$this->get('', SegmentController::class . ':fetch');
								$this->delete('', SegmentController::class . ':delete');
								$this->put('', SegmentController::class . ':update');
								$this->patch('', SegmentController::class . ':update');
								$this->get('/reach', SegmentController::class . ':refreshReachForPersistentSegment');
								$this->get('/data', SegmentController::class . ':data');
								$this->get('/data-automated', SegmentController::class . ':dataAutomated');
								$this->post('/send', SegmentMarketingController::class . ':sendCampaign');
							}
						);
					}
				);
				$this->group(
					'/marketing',
					function () {
						$this->group(
							'/campaign',
							function () {
								$this->post('', MarketingController::class . ':createCampaign');
								$this->group(
									'/{id}',
									function () {
										$this->get('', MarketingController::class . ':getCampaign');
										$this->put('', MarketingController::class . ':updateCampaign');
									}
								);
							}
						);
					}
				);
				$this->group(
					'/menu',
					function () {
						$this->get('', MenuController::class . ':getMenuItems');
						$this->post('', MenuController::class . ':createMenuItem');
						$this->put('/{id}', MenuController::class . ':updateMenuItem');
					}
				);
				$this->group(
					'/loyalty',
					function () {
						$this->group(
							'/stamp',
							function () {
								$this->group(
									'/schemes',
									function () {
										$this->post('', OrganizationStampCardController::class . ':createScheme');
										$this->get('', OrganizationStampCardController::class . ':getSchemes');
										$this->group(
											'/{schemeId}',
											function () {
												$this->group(
													'/secondary-id',
													function () {
														$this->get('', OrganizationStampCardController::class . ':getSecondaryIds');
														$this->post('', OrganizationStampCardController::class . ':createSecondaryId');
														$this->put('/{id}', OrganizationStampCardController::class . ':updateSecondaryId');
													}
												);
												$this->delete('', OrganizationStampCardController::class . ':deleteScheme');
												$this->get('', OrganizationStampCardController::class . ':getScheme');
												$this->put('', OrganizationStampCardController::class . ':updateScheme');
												$this->group(
													'/users',
													function () {
														$this->get('', OrganizationStampCardController::class . ':getSchemeUsers');
														$this->group(
															'/{profileId}',
															function () {
																$this->get('', OrganizationStampCardController::class . ':getSchemeUser');
																$this->delete('', OrganizationStampCardController::class . ':removeUserFromScheme');
																$this->post('/stamps', OrganizationStampCardController::class . ':giveUserStamps');
																$this->post('/rewards/{rewardId}', OrganizationStampCardController::class . ':redeemUsersReward');
															}
														);
													}
												);
											}
										);
									}
								);
							}
						);
					}
				);
				$this->group(
					'/settings',
					function () {
						$this->get('', OrganizationSettingsController::class . ':getSettings');
						$this->put('', OrganizationSettingsController::class . ':updateSettings');
					}
				);
				$this->group(
					'/reports',
					function () {
						$this->get('/users', ReportsController::class . ':getOrganisationRegistrations');
						$this->group('/customers', function () {
							$this->get('/totals', ReportsController::class . ':getOrganisationRegistrationsTotals');
							$this->get('/table', ReportsController::class . ':getOrganisationRegistrationsTable');
						});
						$this->get('/origin', OriginReportController::class . ':getOriginReport');
						$this->group('/interactions', function () {
							$this->get('/totals', ReportsController::class . ':getOrganisationRegistrations');
						});
					}
				);
				$this->group(
					'/interactions',
					function () {
						$this->get('', InteractionsController::class . ':fetchInteractions');
						$this->get('/registrations', InteractionsController::class . ':fetchRegistrationSource');
						$this->get('/totals', InteractionsController::class . ':fetchRegistrationTotalSource');
						$this->get('/sources', InteractionsController::class . ':fetchDataSources');
					}
				);
				$this->get('/progress', OrganisationProgress::class . ':get');
				$this->group(
					'/form',
					function () {
						$this->get('', Settings::class . ':getForms');
						$this->post('', Settings::class . ':createForm');
						$this->group(
							'/{id}',
							function () {
								$this->put('', Settings::class . ':updateForm');
								$this->get('', Settings::class . ':getFormRequest');
								$this->delete('', Settings::class . ':deleteForm');
							}
						);
					}
				);
				$this->group(
					'/website',
					function () {
						$this->get('', WebTrackingController::class . ':listWebsites');
						$this->post('', WebTrackingController::class . ':createWebsite');
						$this->group(
							'/{id}',
							function () {
								$this->put('', WebTrackingController::class . ':updateWebsite');
								$this->get('/events', WebTrackingController::class . ':getEvents');
								$this->get('/live', WebTrackingController::class . ':getLiveEvents');
							}
						);
					}
				);
				$this->group(
					'/billing',
					function () {
						$this->get('/sms', Subscription::class . ':getSmsLedger');
						$this->post('/sms-deduct', Subscription::class . ':deductSmsLedger');
						$this->post('/sms', Subscription::class . ':addCreditHostedPageRoute');
						$this->post('/venue', Subscription::class . ':postAddVenue');
						$this->get('/portal', _ChargeBeeCustomerController::class . ':createPortalRoute');
						$this->post('', Subscription::class . ':postSubscription');
						$this->put('/{subscriptionId}', Subscription::class . ':putSubscription');
						$this->get('', Subscription::class . ':getSubscription');
					}
				)
					->add(AuthMiddleware::class)
					->add(RoleConfig::super());
				$this->group(
					'/crm',
					function () {
						$this->post('/users', UserOverviewController::class . ':getRoute');
					}
				);
				$this->group(
					'/import',
					function () {
						$this->post('/csv', UploadController::class . ':upload');
					}
				);
				$this->group(
					'/gifting',
					function () {
						$this->get('/search', GiftCardController::class . ':search');
						$this->get('/reporting', GiftCardController::class . ':reporting');
						$this->get('', Gifting::class . ':getAllGiftingSettings');
						$this->post('', Gifting::class . ':createGiftingSettings');
						$this->group(
							'/cards',
							function () {
								$this->get('', GiftCardController::class . ':fetchAllGiftCards');
								$this->put('/{id}/owner', GiftCardController::class . ':changeOwner');
								$this->post('/{id}/resend', GiftCardController::class . ':resendEmail');
							}
						);
						$this->group(
							"/schemes/{id}",
							function () {
								$this->get('', Gifting::class . ':getGiftingSettings');
								$this->put('', Gifting::class . ':updateGiftingSettings');
							}
						);
					}
				);
				$this->put('', OrganizationsController::class . ':updateOrganisationRoute');
				$this->get('', OrganizationsController::class . ':getOrganisationRoute');
				$this->put('/parent', OrganizationsController::class . ':setParentRoute');
				$this->group(
					'/children',
					function () {
						$this->get('', OrganizationsController::class . ':getChildrenRoute');
						$this->put('', OrganizationsController::class . ':setChildrenRoute');
						$this->post('', OrganizationsController::class . ':addNewChildRoute');
					}
				);
				$this->group(
					'/pricing',
					function () {
						$this->put('', CustomerPricingController::class . ':updateRoute')->add(OrgTypeMiddleware::class)
							->add(AuthMiddleware::class)
							->add(OrgTypeRoleConfig::superRoot());
						$this->get('', CustomerPricingController::class . ':getRoute');
						$this->get('/plans', _SubscriptionPlanController::class . ':findBestSuitedPlansRoute');
					}
				);
				$this->group(
					'/gallery',
					function () {
						$this->get('', _GalleryController::class . ':getImages');
						$this->delete('/{id}', _GalleryController::class . ':deleteImageRoute');
						$this->post('', _GalleryController::class . ':createGalleryImageRoute');
					}
				);
				$this->group(
					'/locations',
					function () {
						$this->get('/insights/overview', OverviewController::class . ':getOverview');

						$this->post('', OrganizationsController::class . ':addLocationRoute');
						$this->put('', OrganizationsController::class . ':setLocationsRoute');
						$this->get('', OrganizationsController::class . ':getLocationsRoute');
						$this->get('/{serial}', OrganizationsController::class . ':getSingleLocationRoute')
							->add(AuthMiddleware::class)
							->add(RoleConfig::all());
					}
				);
				$this->group(
					'/users',
					function () {
						$this->get('', OrganizationsController::class . ':getUsersRoute');
						$this->post('', OrganizationsController::class . ':addUserRoute');
						$this->group('/locations', function () {
							$this->put('', OrganizationsController::class . ':updateLocationUserRoute');
							$this->get('', OrganizationsController::class . ':getLocationUserRoute');
						});
						$this->delete('/{uid}', OrganizationsController::class . ':removeUserRoute');
					}
				);
				$this->group(
					'/filters',
					function () {
						$this->get('', FilterListController::class . ':getAllRoute');
						$this->put('', FilterListController::class . ':updateOrCreateRoute');
						$this->group(
							'/{filterId}',
							function () {
								$this->get('', FilterListController::class . ':getRoute');
								$this->delete('', FilterListController::class . ':deleteRoute');
							}
						);
					}
				);
				$this->group(
					'/policies',
					function () {
						$this->post('', LocationPolicyController::class . ':createPolicyRoute');
						$this->get('', LocationPolicyController::class . ':listPolicyBelongingToAdminRoute');
						$this->group(
							'/{groupId}',
							function () {
								$this->get('', LocationPolicyController::class . ':getSitesBelongingToGroupRoute');
								$this->delete('', LocationPolicyController::class . ':deleteGroupRoute');
								$this->put('', LocationPolicyController::class . ':updatePolicyNameRoute');
							}
						);
					}
				);
				$this->group(
					'/templates',
					function () {
						$this->post('', _MarketingUserGroupController::class . ':createOrUpdateRoute');
						$this->get('', _MarketingUserGroupController::class . ':getAllExistingRoute');
						$this->group(
							'/{id}',
							function () {
								$this->get('', _MarketingUserGroupController::class . ':getTemplateRoute');
								$this->delete('', _MarketingUserGroupController::class . ':deleteRoute');
							}
						);
					}
				);
			}
		)
			->add(AuthMiddleware::class)
			->add(RoleConfig::all());
	}
)
	->add(LegacyCompatibilityMiddleware::class)
	->add(AuthMiddleware::class);
