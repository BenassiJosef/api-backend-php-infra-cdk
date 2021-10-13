<?php

/**
 * Created by jamieaitken on 25/10/2018 at 13:33
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Migrations\_MigrationsController;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\OrgTypeRoleConfigurationMiddleware;
use App\Policy\Auth;

$app->group(
    '/migration',
    function () {
        $this->get('', App\Controllers\Migrations\_MigrationsController::class . ':backDateReviewsRoute');
        $this->get('/members', App\Controllers\Migrations\_MigrationsController::class . ':migrateMembersRoute');
        $this->put('/branding/{serial}', App\Controllers\Migrations\_MigrationsController::class . ':migrateBrandingRoute');
        $this->post('/createTypes', App\Controllers\Migrations\_MigrationsController::class . ':createTypesRoute');
        $this->get('/paypal', App\Controllers\Migrations\_MigrationsController::class . ':migratePaypalRoute');
        $this->get('/images/{offset}', App\Controllers\Migrations\_MigrationsController::class . ':migrateImages');
        $this->get('/invoices', App\Controllers\Migrations\_MigrationsController::class . ':migrateInvoicesRoute');
        $this->get('/unifi', App\Controllers\Migrations\_MigrationsController::class . ':migrateUniFiControllersRoute');
        $this->get('/vacant', App\Controllers\Migrations\_MigrationsController::class . ':vacantSitesRoute');
        $this->get(
            '/notification-type',
            App\Controllers\Migrations\_MigrationsController::class . ':enforceAdminsToHaveDefaultNotificationsRoute'
        );
        $this->get(
            '/networkSettings',
            App\Controllers\Migrations\_MigrationsController::class . ':normalizeNetworkSettingsRoute'
        );
        $this->get('/emailReports', App\Controllers\Migrations\_MigrationsController::class . ':enforceEmailReportsRoute');
        $this->get('/schedule', App\Controllers\Migrations\_MigrationsController::class . ':mergeScheduleRoute');
        $this->get('/symlinks', _MigrationsController::class . ':createVirtualSerialsRoute');
        $this->get('/html', _MigrationsController::class . ':migrateEverythingToHtmlTemplateTypeRoute');
    }
)->add(OrgTypeMiddleware::class)
    ->add(new OrgTypeRoleConfigurationMiddleware(
        [Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller],
        [Organization::RootType]
    ))
    ->add(Auth::class);
