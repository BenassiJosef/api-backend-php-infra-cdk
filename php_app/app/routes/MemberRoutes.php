<?php

/**
 * Created by jamieaitken on 19/03/2018 at 10:49
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Billing\Subscriptions\LocationSubscriptionController;
use App\Controllers\Billing\Subscriptions\Cancellation\CancellationController;
use App\Controllers\Branding\_PartnerBranding;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeePaymentSourceController;
use App\Controllers\Integrations\GoCardless\_GoCardlessController;
use App\Controllers\Members\_MembersController;
use App\Controllers\Members\LogoutController;
use App\Controllers\Members\MemberWorthController;
use App\Controllers\Notifications\_NotificationsSendType;
use App\Controllers\Notifications\FirebaseCloudMessagingController;
use App\Controllers\User\MeController;
use App\Package\Auth\Access\Config\OrgTypeRoleConfig;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\LegacyCompatibilityMiddleware;
use App\Package\Member\MemberAccessController;

$app->group(
    '/members/{uid}',
    function () {
        $this->group(
            '/worth',
            function () {
                $this->get(
                    '',
                    MemberWorthController::class . ':calculateWorthRoute'
                );
                $this->get(
                    '/top',
                    MemberWorthController::class . ':topEarnersRoute'
                );
            }
        )
            ->add(AuthMiddleware::class)
            ->add(OrgTypeRoleConfig::superRoot());

        $this->group(
            '/branding',
            function () {
                $this->put('', _PartnerBranding::class . ':savePartnerBrandingRoute');
            }
        );

        $this->group(
            '/payment_methods/settings/credits',
            function () {
                $this->post(
                    '',
                    _ChargeBeeCustomerController::class . ':addPromotionalCreditsRoute'
                );
                $this->delete(
                    '',
                    _ChargeBeeCustomerController::class . ':deletePromotionalCreditsRoute'
                );
            }
        )
            ->add(AuthMiddleware::class)
            ->add(OrgTypeRoleConfig::superRoot());

        $this->group(
            '/payment_methods',
            function () {
                $this->group(
                    '/settings',
                    function () {
                        $this->put(
                            '',
                            _ChargeBeeCustomerController::class . ':updateCustomerAddressRoute'
                        );
                    }
                );

                $this->get('/gg', _GoCardlessController::class . ':getLinkRoute');
                $this->get(
                    '',
                    App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController::class . ':getCustomerPaymentSourcesRoute'
                );
                $this->put(
                    '/primary',
                    _ChargeBeeCustomerController::class . ':updatePaymentRoleToPrimaryRoute'
                );
                $this->post(
                    '',
                    App\Controllers\Integrations\ChargeBee\_ChargeBeePaymentSourceController::class . ':addPaymentSourceRoute'
                );
                $this->delete(
                    '/{id}',
                    _ChargeBeePaymentSourceController::class . ':deletePaymentSourceRoute'
                );
            }
        );

        $this->group(
            '/subscriptions',
            function () {
                $this->group(
                    '/cancellation-requests',
                    function () {
                        $this->post('', CancellationController::class . ':requestCancellation');
                    }
                );
                $this->put('/{id}', LocationSubscriptionController::class . ':updateSubscriptionRoute');

                $this->post(
                    '',
                    LocationSubscriptionController::class . ':createSubscriptionRoute'
                );
            }
        );

        $this->group(
            '/billing-date',
            function () {
                $this->get(
                    '',
                    _ChargeBeeCustomerController::class . ':getCustomerFromChargeBeeRoute'
                );
                $this->put('', _ChargeBeeCustomerController::class . ':changeBillingDateRoute');
            }
        )
            ->add(AuthMiddleware::class)
            ->add(OrgTypeRoleConfig::superRoot());


        $this->group(
            '/notifications',
            function () {
                $this->group(
                    '/push',
                    function () {
                        $this->put(
                            '',
                            FirebaseCloudMessagingController::class . ':createUpdateTokenRoute'
                        );
                        $this->get('', FirebaseCloudMessagingController::class . ':getTokenRoute');
                    }
                );
                $this->put('', _NotificationsSendType::class . ':updateViaKeyRoute');
                $this->get('', _NotificationsSendType::class . ':getRoute');
                $this->delete('', _NotificationsSendType::class . ':deleteRoute');
            }
        );
        $this->delete('/logout', LogoutController::class . ':logoutRoute');
        $this->get('/info', MeController::class . ':getRoute');
        $this->group('/organizations', function () {
            $this->get('', MemberAccessController::class . ':organizations');
        });
        $this->group('/locations', function () {
            $this->get('', MemberAccessController::class . ':locations');
        });
        $this->get('/member', _MembersController::class . ':getMemberRoute');
        $this->put('', _MembersController::class . ':updateRoute');
    }
)
    ->add(LegacyCompatibilityMiddleware::class)
    ->add(AuthMiddleware::class);

$app->get('/members/search', _MembersController::class . ':search')
    ->add(AuthMiddleware::class)
    ->add(OrgTypeRoleConfig::superRoot());
