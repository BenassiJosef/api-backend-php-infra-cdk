<?php

/**
 * Created by jamieaitken on 19/03/2018 at 12:40
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\ConnectedIntegrationController;
use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Integrations\Radius\_RadiusController;
use App\Models\Role;
use App\Package\Organisations\ResourceRoleConfigurationMiddleware;
use App\Package\Organisations\ResourceRoleMiddleware;
use App\Policy\Auth;
use App\Utils\_GeoInfoViaIP;

$app->group(
    '/integration/{orgId}',
    function () {

        require_once('integrations/PayPalRoutes.php');
        require_once('integrations/UniFiRoutes.php');
        require_once('integrations/ZapierRoutes.php');
        require_once('integrations/TextLocalRoutes.php');
        require_once('integrations/MailChimpRoutes.php');
        require_once('integrations/DotMailerRoutes.php');
        require_once('integrations/ConstantContactRoutes.php');
        require_once('integrations/CampaignMonitorRoutes.php');
        require_once('integrations/AirshipRoutes.php');
        require_once('integrations/StripeRoutes.php');
        $this->get('/connected', ConnectedIntegrationController::class . ':isConnectedRoute');
    }
)->add(ResourceRoleMiddleware::class)
    ->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller]))
    ->add(Auth::class);

$app->group(
    '/countryCode',
    function () {
        $this->get('', _GeoInfoViaIP::class . ':createOrFetchRoute');
    }
);

$app->group(
    '/hooks',
    function () {
        $this->post('', _HooksController::class . ':subscribeRoute');
    }
)->add(Auth::class);

$app->group(
    '/radius',
    function () {
        $this->group(
            '/secret/{serial}',
            function () {
                $this->put('', _RadiusController::class . ':updateSecretRoute');
                $this->get('', _RadiusController::class . ':getSecretInDashboardRoute');
            }
        );
    }
)->add(ResourceRoleMiddleware::class)
    ->add(new ResourceRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller]))
    ->add(Auth::class);
