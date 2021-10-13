<?php

/**
 * Created by jamieaitken on 19/03/2018 at 11:00
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Auth\_GoogleOauthClient;
use App\Controllers\Auth\_LegacyAuthController;
use App\Controllers\Auth\_MagicLink;
use App\Controllers\Auth\_oAuth2TokenController;
use App\Controllers\Integrations\ConstantContact\ConstantContactAuthorize;
use App\Models\Organization;
use App\Models\Role;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\Access\Config\ProfileWhitelistMiddleware;
use App\Package\Auth\Access\Config\RoleConfig;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\Controller\ExternalServiceController;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\OrgTypeRoleConfigurationMiddleware;
use App\Policy\Auth;

$app
	->post('/auth/check', ExternalServiceController::class)
	->add(AuthMiddleware::class)
	->add(ProfileWhitelistMiddleware::class);

$app->group(
	'/oauth',
	function () {
		$this->post('/token', _oAuth2TokenController::class . ':token');
		$this->post('/authorize', _oAuth2TokenController::class . ':authorize')
			->add(Auth::class);
		$this->get(
			'/test',
			_oAuth2TokenController::class . ':isLoggedInRoute'
		)->add(AuthMiddleware::class);
		$this->put('/legacy', _LegacyAuthController::class . ':put');
		$this->group(
			'/google',
			function () {
				$this->get('', _GoogleOauthClient::class . ':getLink');
				$this->get('/callback', _GoogleOauthClient::class . ':callback');
			}
		);
		$this->group(
			'/magic',
			function () {
				$this->get('', _MagicLink::class . ':getRoute');
				$this->post('/nearly', _MagicLink::class . ':generateNearlyLinkRoute');
				$this->post(
					'',
					_MagicLink::class . ':postRoute'
				)
					->add(OrgTypeMiddleware::class)
					->add(new OrgTypeRoleConfigurationMiddleware([Role::LegacyAdmin, Role::LegacySuperAdmin, Role::LegacyReseller], [Organization::RootType]))
					->add(Auth::class);
			}
		);
		$this->group(
			'/constant-contact',
			function () {
				$this->get(
					'',
					ConstantContactAuthorize::class . ':getAccessTokenRoute'
				);
			}
		);
	}
);
