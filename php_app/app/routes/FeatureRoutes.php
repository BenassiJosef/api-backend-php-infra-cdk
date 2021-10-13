<?php
/**
 * Created by jamieaitken on 25/10/2018 at 12:49
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Notifications\_FeatureRequestNotifyController;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\OrgTypeRoleConfigurationMiddleware;
use App\Policy\Auth;

$app->group(
    '/features', function () {
    $this->post('', _FeatureRequestNotifyController::class . ':createFeatureRequestRoute');
    $this->delete('', _FeatureRequestNotifyController::class . ':deleteFeatureRequestRoute')
         ->add(OrgTypeMiddleware::class)
         ->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]))
         ->add(Auth::class);
    $this->get('', _FeatureRequestNotifyController::class . ':loadFeatureRequestsRoute');
    $this->get('/{id}', _FeatureRequestNotifyController::class . ':loadFeatureRoute');
    $this->put('/{id}', _FeatureRequestNotifyController::class . ':submitVoteRoute');
    $this->put('/{id}/update', _FeatureRequestNotifyController::class . ':updateFeatureRoute')
         ->add(OrgTypeMiddleware::class)
         ->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]))
         ->add(Auth::class);
}
);
