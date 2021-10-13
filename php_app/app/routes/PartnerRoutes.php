<?php
/**
 * Created by jamieaitken on 19/03/2018 at 12:19
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Billing\Quotes\_QuotesController;
use App\Controllers\Schedule\_PartnerNetRevenue;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Member\PartnerCustomerController;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\OrgTypeRoleConfigurationMiddleware;
use App\Package\Organisations\ResourceRoleConfigurationMiddleware;
use App\Package\Organisations\ResourceRoleMiddleware;
use App\Package\Upload\UploadController;

$app->group('/partner/organisation/{resellerOrgId}', function () {
    $this->group('/quotes', function () {
        $this->get('', _QuotesController::class . ':getsRoute');
        $this->get('/{id}', _QuotesController::class . ':partnerGetRoute');
    });

    $this->group('/customers', function () {
        $this->post('', PartnerCustomerController::class . ':createCustomer');
        $this->group('/{customerOrgId}/quotes', function () {
            $this->put('/{id}', _QuotesController::class . ':putRoute');
            $this->post('', _QuotesController::class . ':postRoute');
            $this->post('/{id}', _QuotesController::class . ':sendQuoteRoute');
        });
    });
    $this->get('/stats', _PartnerNetRevenue::class . ':getRoute');

})->add(ResourceRoleMiddleware::class)
    ->add(new ResourceRoleConfigurationMiddleware([
        Role::LegacyAdmin,
        Role::LegacySuperAdmin,
        Role::LegacyReseller
    ]))
    ->add(OrgTypeMiddleware::class)
    ->add(new OrgTypeRoleConfigurationMiddleware([
        Role::LegacyAdmin,
        Role::LegacySuperAdmin,
        Role::LegacyReseller
    ], [
        Organization::RootType,
        Organization::ResellerType
    ]))
    ->add(App\Policy\Auth::class);