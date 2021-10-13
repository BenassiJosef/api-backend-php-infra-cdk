<?php

use App\Controllers\Billing\Subscriptions\_HostedPages;
use App\Controllers\Branding\_BrandingController;
use App\Controllers\HealthcheckController;
use App\Controllers\Integrations;
use App\Models\Role;
use App\Package\Response\ExceptionMiddleware;
use App\Policy;

/**
 * POLICY
 */

$checkProxyHeaders = true;
$trustedProxies    = ['10.0.0.1', '10.0.0.2'];

$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies))->add(ExceptionMiddleware::class);

$app->get('/', HealthcheckController::class . ':ping');
$app->get('/status', HealthcheckController::class . ':getStatus');


require_once('routes/PublicRoutes.php');

require_once('routes/TestRoutes.php');

require_once('routes/LoyaltyAppRoutes.php');

require_once('routes/OAuthRoutes.php');

require_once('routes/SMSRoutes.php');

$app->group(
    '/billing', function () {
    $this->post('', _HostedPages::class . ':postHostedPages');
}
)->add(new Policy\Role(Role::LegacyReseller))->add(Policy\Auth::class);


require_once('routes/MarketingRoutes.php');

require_once('routes/PasswordRoutes.php');

require_once('routes/MemberRoutes.php');

require_once('routes/PartnerRoutes.php');

require_once('routes/LocationRoutes.php');

require_once('routes/RegistrationRoutes.php');

require_once('routes/NearlyRoutes.php');

require_once('routes/UserRoutes.php');

require_once('routes/IntegrationRoutes.php');

require_once('routes/InformRoutes.php');

require_once('routes/FeatureRoutes.php');

require_once('routes/ReleasesRoutes.php');

require_once('routes/MigrationRoutes.php');

require_once('routes/StripeRoutes.php');

$app->group(
    '/go-cardless', function () {
    $this->get('/callback', Integrations\GoCardless\_GoCardlessController::class . ':callbackRoute');
}
);

$app->group(
    '/payments', function () {
    $this->post('', App\Controllers\Payments\_PaymentsController::class . ':createPaymentRoute');
    $this->put('/{id}', App\Controllers\Payments\_PaymentsController::class . ':updatePaymentRoute');
}
);

require_once('routes/FacebookRoutes.php');

$app->group(
    '/branding', function () {
    $this->get('', _BrandingController::class . ':getBrandingRoute');
}
);

require_once('routes/WebhookRoutes.php');
require_once('routes/WorkerRoutes.php');
require_once('routes/ScheduleRoutes.php');
require_once('routes/OrganisationRoutes.php');
require_once('routes/WebTrackingRoutes.php');
