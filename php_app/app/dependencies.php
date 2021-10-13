<?php

use App\Controllers\Auth\_GoogleOauthClient;
use App\Controllers\Auth\_LegacyAuthController;
use App\Controllers\Auth\_MagicLink;
use App\Controllers\Auth\_oAuth2Controller;
use App\Controllers\Auth\_oAuth2TokenController;
use App\Controllers\Auth\_PasswordController;
use App\Controllers\Billing\Quotes\QuoteCreator;
use App\Controllers\Billing\Quotes\_QuotesController;
use App\Controllers\Billing\Subscription;
use App\Controllers\Billing\Subscriptions;
use App\Controllers\Billing\Subscriptions\Cancellation\CancellationController;
use App\Controllers\Billing\Subscriptions\DummySubscriptionCreator;
use App\Controllers\Billing\Subscriptions\LocationSubscriptionController;
use App\Controllers\Billing\Subscriptions\SubscriptionCreator;
use App\Controllers\Billing\Subscriptions\_SubscriptionPlanController;
use App\Controllers\Billing\Webhooks\ChargeBeeWebHookController;
use App\Controllers\Branding\_PartnerBranding;
use App\Controllers\Clients\_ClientsActiveController;
use App\Controllers\Clients\_ClientsController;
use App\Controllers\Clients\_ClientsUpdateController;
use App\Controllers\Filtering\FilterListController;
use App\Controllers\Gifting\Gifting;
use App\Controllers\HealthcheckController;
use App\Controllers\Integrations;
use App\Controllers\Integrations\Airship\AirshipContactController;
use App\Controllers\Integrations\ChargeBee\ChargeBeeEventGetter;
use App\Controllers\Integrations\ChargeBee\Stubs\StubEventGetter;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeCustomerController;
use App\Controllers\Integrations\ChargeBee\_ChargeBeeEventController;
use App\Controllers\Integrations\dotMailer\DotMailerContactController;
use App\Controllers\Integrations\IgniteNet\_IgniteNetController;
use App\Controllers\Integrations\IgniteNet\_IgniteNetInformController;
use App\Controllers\Integrations\Mail;
use App\Controllers\Integrations\MailChimp\MailChimpContactController;
use App\Controllers\Integrations\Mikrotik\_MikrotikFacebookController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\RabbitMQ\MailChimpSendWorker;
use App\Controllers\Integrations\RabbitMQ\TextLocalSendWorker;
use App\Controllers\Integrations\SNS\_QueueController;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\Stripe\_StripeSubscriptionsController;
use App\Controllers\Integrations\Stripe\_StripeSubscriptionsItemsController;
use App\Controllers\Integrations\Textlocal\TextLocalContactController;
use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Controllers\Locations\Debugger;
use App\Controllers\Locations\Devices\_ConnectedController;
use App\Controllers\Locations\Devices\_LocationsDevicesController;
use App\Controllers\Locations\Devices\_WhitelistController;
use App\Controllers\Locations\Info\LocationsInfoController;
use App\Controllers\Locations\MenuGenerator\MenuGenerator;
use App\Controllers\Locations\Pricing;
use App\Controllers\Locations\Reports\FakeReports\FakeConnectionReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeCustomerReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeDeviceReportController;
use App\Controllers\Locations\Reports\FakeReports\FakePaymentsReportController;
use App\Controllers\Locations\Reports\FakeReports\FakeRegistrationsReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\BrandingReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\CaptureReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\ConnectionLimitExceededController;
use App\Controllers\Locations\Reports\MultiSiteReports\GeneralReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\MultiSiteReportController;
use App\Controllers\Locations\Reports\MultiSiteReports\MultiSiteReportProducer;
use App\Controllers\Locations\Reports\MultiSiteReports\NearlyImpressionsMasterController;
use App\Controllers\Locations\Reports\MultiSiteReports\TopPerformingCustomersController;
use App\Controllers\Locations\Reports\Overview\ConnectionsView;
use App\Controllers\Locations\Reports\Overview\ImpressionsView;
use App\Controllers\Locations\Reports\Overview\OverviewController;
use App\Controllers\Locations\Reports\Overview\OverviewView;
use App\Controllers\Locations\Reports\Overview\ReviewsView;
use App\Controllers\Locations\Reports\Overview\UsersView;
use App\Controllers\Locations\Reports\Overview\View;
use App\Controllers\Locations\Reports\PredictiveReports\PredictConnectionsReportController;
use App\Controllers\Locations\Reports\ReportController;
use App\Controllers\Locations\Reports\SplashScreenImpressions;
use App\Controllers\Locations\Reports\_LocationReportController;
use App\Controllers\Locations\Reviews\LocationReviewController;
use App\Controllers\Locations\Reviews\LocationReviewErrorController;
use App\Controllers\Locations\Reviews\LocationReviewTimelineController;
use App\Controllers\Locations\Settings\Bandwidth\_BandwidthController;
use App\Controllers\Locations\Settings\Branding\BrandingController;
use App\Controllers\Locations\Settings\Branding\_LogoUploadController;
use App\Controllers\Locations\Settings\Capture\_CaptureController;
use App\Controllers\Locations\Settings\General\_GeneralController;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Controllers\Locations\Settings\Policy\LocationPolicyController;
use App\Controllers\Locations\Settings\Position\LocationPositionController;
use App\Controllers\Locations\Settings\Social\LocationFacebookController;
use App\Controllers\Locations\Settings\Templating\LocationTemplateController;
use App\Controllers\Locations\Settings\Timeouts\_TimeoutsController;
use App\Controllers\Locations\Settings\Type\LocationTypeController;
use App\Controllers\Locations\Settings\Type\LocationTypeSerialController;
use App\Controllers\Locations\Settings\Type\LocationTypeSerialReportController;
use App\Controllers\Locations\Settings\WiFi\_WiFiController;
use App\Controllers\Locations\Settings\_LocationNetworkSettings;
use App\Controllers\Locations\Settings\_LocationScheduleController;
use App\Controllers\Locations\Settings\_LocationSettingsController;
use App\Controllers\Locations\Super\_SuperController;
use App\Controllers\Locations\_LocationCreationController;
use App\Controllers\Locations\_LocationsController;
use App\Controllers\Marketing\Campaign\CampaignEmailSender;
use App\Controllers\Marketing\Campaign\CampaignsController;
use App\Controllers\Marketing\Campaign\CampaignSMSSender;
use App\Controllers\Marketing\Campaign\URLShortener\URLShortenerController;
use App\Controllers\Marketing\Campaign\_AudienceController;
use App\Controllers\Marketing\Campaign\_PreviewCampaignController;
use App\Controllers\Marketing\Gallery\_GalleryController;
use App\Controllers\Marketing\Report\URLShortenerEventController;
use App\Controllers\Marketing\Report\_MarketingReportController;
use App\Controllers\Marketing\Template\_BaseTemplateController;
use App\Controllers\Marketing\Template\_MarketingUserGroupController;
use App\Controllers\Marketing\_MarketingCallBackController;
use App\Controllers\Marketing\_MarketingLegacy;
use App\Controllers\Marketing\_MarketingRunner;
use App\Controllers\Members\CustomerPricingController;
use App\Controllers\Members\LogoutController;
use App\Controllers\Members\MemberValidationController;
use App\Controllers\Members\MemberWorthController;
use App\Controllers\Members\_MembersController;
use App\Controllers\Nearly\Validations\EmailValidator;
use App\Controllers\Nearly\NearlyAuthController;
use App\Controllers\Nearly\NearlyGDPRCompliance;
use App\Controllers\Nearly\NearlyImpressionController;
use App\Controllers\Nearly\NearlyPayPalController;
use App\Controllers\Nearly\NearlyProfileController;
use App\Controllers\Nearly\NearlyProfileOptOut;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileAccountService;
use App\Controllers\Nearly\NearlyProfile\NearlyProfileDownloadController;
use App\Controllers\Nearly\Stories\NearlyStoryController;
use App\Controllers\Nearly\Stories\NearlyStoryTrackingController;
use App\Controllers\Nearly\_NearlyController;
use App\Controllers\Nearly\_NearlyDevicesController;
use App\Controllers\Notifications\FirebaseCloudMessagingController;
use App\Controllers\Notifications\_ChangelogController;
use App\Controllers\Notifications\_FeatureRequestNotifyController;
use App\Controllers\Notifications\_NotificationSettingsController;
use App\Controllers\Notifications\_NotificationsSendType;
use App\Controllers\Notifications\_ReleaseNotifyController;
use App\Controllers\Organizations\OrganizationsController;
use App\Controllers\Registrations;
use App\Controllers\Schedule\QuoteScheduler;
use App\Controllers\Schedule\RemoveIncompleteCampaigns;
use App\Controllers\Schedule\ReviewSchedule;
use App\Controllers\Schedule\_DeformController;
use App\Controllers\Schedule\_EmailReports;
use App\Controllers\Schedule\_PartnerNetRevenue;
use App\Controllers\Schedule\_PostCodeBuilder;
use App\Controllers\Schedule\_ValidationTimeoutsController;
use App\Controllers\SMS\RandomOptOutCodeGenerator;
use App\Controllers\SMS\_SMSController;
use App\Controllers\Stats\StatsController;
use App\Controllers\User\MeController;
use App\Controllers\User\MeRepository;
use App\Controllers\User\UserOverviewController;
use App\Controllers\User\_UserController;
use App\Controllers\WebTracker\WebTrackingController;
use App\Filters\TwigFilters;
use App\Models\Loyalty\LoyaltyStampCardEvent;
use App\Package\Async\BatchedQueue;
use App\Package\Async\Notifications\SNS\SNSConfig;
use App\Package\Async\Notifications\SNS\SNSNotifier;
use App\Package\Async\Queue;
use App\Package\Async\QueueConfig;
use App\Package\Auth\Access\Config\ProfileWhitelistMiddleware;
use App\Package\Auth\Access\User\UserRequestValidatorFactory;
use App\Package\Auth\AuthMiddleware;
use App\Package\Auth\Controller\ExternalServiceController;
use App\Package\Auth\ExternalServices\AccessChecker;
use App\Package\Auth\LegacyCompatibilityMiddleware;
use App\Package\Auth\Tokens\AccessTokenRepository;
use App\Package\Auth\Tokens\AccessTokenSource;
use App\Package\Auth\Tokens\TokenFactory;
use App\Package\Auth\Tokens\TokenSource;
use App\Package\Billing\SMSTransactions;
use App\Package\Clients\Delorean\DeloreanClient;
use App\Package\Clients\Delorean\DeloreanConfig;
use App\Package\Clients\InternalOAuth\ClientCredentialsConfig;
use App\Package\Clients\InternalOAuth\ClientCredentialsTokenSource;
use App\Package\Database\RawStatementExecutor;
use App\Package\DataSources\EmailingProfileInteractionFactory;
use App\Package\DataSources\Hooks\AutoReviewHook;
use App\Package\DataSources\Hooks\AutoServiceHook;
use App\Package\DataSources\Hooks\AutoStampingHook;
use App\Package\DataSources\Hooks\HookNotifier;
use App\Package\DataSources\Hooks\LoggingHook;
use App\Package\DataSources\Hooks\NewRelicHook;
use App\Package\DataSources\InteractionController;
use App\Package\DataSources\InteractionService;
use App\Package\DataSources\OptInService;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\DataSources\StatementExecutor;
use App\Package\Domains\Registration\UserRegistrationRepository;
use App\Package\Filtering\UserFilter;
use App\Package\GiftCard\GiftCardController;
use App\Package\GiftCard\GiftCardService;
use App\Package\Interactions\InteractionsController;
use App\Package\Location\LocationController;
use App\Package\Location\LocationProvider;
use App\Package\Loyalty\Events\EmailNotifier;
use App\Package\Loyalty\Events\InAppNotificationNotifier;
use App\Package\Loyalty\Events\InteractionNotifier;
use App\Package\Loyalty\Events\LoggingNotifier;
use App\Package\Loyalty\Events\NewRelicNotifier;
use App\Package\Loyalty\Events\Router;
use App\Package\Loyalty\Events\RouterSingleton;
use App\Package\Loyalty\OrganizationLoyaltyServiceFactory;
use App\Package\Loyalty\Presentation\AppStampCardController;
use App\Package\Loyalty\Presentation\OrganizationStampCardController;
use App\Package\Loyalty\ProfileLoyaltyServiceFactory;
use App\Package\Loyalty\Stamps\StampContextFactory;
use App\Package\Marketing\MarketingBounces;
use App\Package\Marketing\MarketingController;
use App\Package\Member\MemberAccessController;
use App\Package\Member\MemberService;
use App\Package\Member\PartnerCustomerController;
use App\Package\Member\ResellerOrganisationService;
use App\Package\Menu\MenuController;
use App\Package\Nearly\NearlyAuthentication;
use App\Package\Nearly\NearlyController;
use App\Package\Notification\InAppNotification;
use App\Package\Organisations\Children\ChildController;
use App\Package\Organisations\Children\ChildRepositoryFactory;
use App\Package\Organisations\LocationAccessChangeRequestProvider;
use App\Package\Organisations\Locations\LocationRepositoryFactory;
use App\Package\Organisations\LocationService;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use App\Package\Organisations\OrganizationSettingsController;
use App\Package\Organisations\OrganizationSettingsService;
use App\Package\Organisations\OrgTypeMiddleware;
use App\Package\Organisations\ResourceRoleMiddleware;
use App\Package\Organisations\UserOrganizationAccessRepositoryFactory;
use App\Package\Organisations\UserRoleChecker;
use App\Package\Profile\Data\DataFetcher;
use App\Package\Profile\Data\Presentation\DataController;
use App\Package\Profile\Data\SubjectLocator;
use App\Package\Profile\ProfileChecker;
use App\Package\Profile\UserProfileProvider;
use App\Package\Reports\Origin\OriginReportController;
use App\Package\Reports\ReportsController;
use App\Package\RequestUser\UserProvider;
use App\Package\Response\ExceptionMiddleware;
use App\Package\Reviews\Controller\ReviewsSettingsController;
use App\Package\Reviews\Controller\ReviewsReportController;
use App\Package\Reviews\Controller\UserReviewController;
use App\Package\Reviews\DelayedReviewSender;
use App\Package\Reviews\ReviewService;
use App\Package\Reviews\Scrapers\FacebookScraper;
use App\Package\Reviews\Scrapers\GoogleScraper;
use App\Package\Reviews\Scrapers\TripadvisorScraper;
use App\Package\Segments\Controller\SegmentController;
use App\Package\Segments\Controller\SegmentMarketingController;
use App\Package\Segments\Database\QueryFactory;
use App\Package\Segments\Marketing\CampaignSenderFactory;
use App\Package\Segments\SegmentRepositoryFactory;
use App\Package\Upload\UploadController;
use App\Package\Vendors\InformController;
use App\Package\Vendors\VendorsController;
use App\Package\WebForms\EmailSignupController;
use App\Package\WebForms\EmailSignupService;
use App\Policy\Auth;
use App\Policy\hasNotificationsSubs;
use App\Policy\inAccess;
use App\Policy\NearlyLogInService;
use App\Utils\CacheEngine;
use App\Utils\PushNotifications;
use App\Utils\Validators\Email;
use App\Utils\_GeoInfoViaIP;
use DoctrineExtensions\Query\Mysql\CharLength;
use DoctrineExtensions\Query\Mysql\Date;
use DoctrineExtensions\Query\Mysql\DateFormat;
use DoctrineExtensions\Query\Mysql\DateSub;
use DoctrineExtensions\Query\Mysql\Day;
use DoctrineExtensions\Query\Mysql\DayOfWeek;
use DoctrineExtensions\Query\Mysql\FromUnixtime;
use DoctrineExtensions\Query\Mysql\GroupConcat;
use DoctrineExtensions\Query\Mysql\Hour;
use DoctrineExtensions\Query\Mysql\IfElse;
use DoctrineExtensions\Query\Mysql\MatchAgainst;
use DoctrineExtensions\Query\Mysql\Minute;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Now;
use DoctrineExtensions\Query\Mysql\Round;
use DoctrineExtensions\Query\Mysql\StrToDate;
use DoctrineExtensions\Query\Mysql\TimestampDiff;
use DoctrineExtensions\Query\Mysql\TimeToSec;
use DoctrineExtensions\Query\Mysql\UnixTimestamp;
use DoctrineExtensions\Query\Mysql\Week;
use DoctrineExtensions\Query\Mysql\Year;
use Doctrine\Common\ClassLoader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use OAuth2\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Doctrine\UuidType;
use Slim\Container;
use Firebase\Database;
use Firebase\Factory;

$container = $app->getContainer();

$container[MemberAccessController::class] = function (Container $container) {
	return new MemberAccessController(
		new UserOrganizationAccessRepositoryFactory(
			$container->get('em'),
			$container->get(UserProvider::class)
		),
		new LocationRepositoryFactory(
			$container->get('em'),
			$container->get(UserProvider::class)
		)
	);
};

$container[DataController::class] = function (Container $container) {
	return new DataController(
		new DataFetcher(
			$container->get('em')
		),
		new SubjectLocator(
			$container->get('em')
		),
		$container->get(RawStatementExecutor::class)
	);
};

$container[ChildController::class] = function (Container $container) {
	return new ChildController(
		new ChildRepositoryFactory(
			$container->get('em'),
			$container->get(OrganizationProvider::class)
		)
	);
};

$container[AccessTokenSource::class] = function (Container $container) {
	return new AccessTokenRepository($container->get('em'));
};

$container[LegacyCompatibilityMiddleware::class] = function (Container $container) {
	return new LegacyCompatibilityMiddleware(
		$container->get(UserRoleChecker::class)
	);
};

$container[TokenSource::class] = function (Container $container) {
	return new TokenFactory(
		$container->get('em'),
		$container->get(AccessTokenSource::class),
		new UserRequestValidatorFactory(
			$container->get(UserRoleChecker::class)
		)
	);
};

$container[ProfileWhitelistMiddleware::class] = function (Container $container) {
	return new ProfileWhitelistMiddleware();
};

$container[AccessChecker::class] = function (Container $container) {
	return new AccessChecker(
		$container->get(TokenSource::class)
	);
};

$container[ExternalServiceController::class] = function (Container $container) {
	return new ExternalServiceController(
		$container->get(AccessChecker::class)
	);
};

$container[AuthMiddleware::class] = function (Container $container) {
	return new AuthMiddleware(
		$container->get('em'),
		$container->get(TokenSource::class)
	);
};

$container[inAccess::class] = function (Container $container) {
	return new inAccess(
		$container->get('em')
	);
};

$container[Client::class] = function (Container $container) {
	return new Client();
};

$container[ClientCredentialsConfig::class] = function (Container $container) {
	return new ClientCredentialsConfig();
};

$container[ClientCredentialsTokenSource::class] = function (Container $container) {
	return new ClientCredentialsTokenSource(
		$container->get(ClientCredentialsConfig::class),
		$container->get(Client::class),
	);
};

$container[DeloreanConfig::class] = function (Container $container) {
	return new DeloreanConfig();
};

$container[DeloreanClient::class] = function (Container $container) {
	return DeloreanClient::make(
		$container->get(DeloreanConfig::class),
		$container->get(ClientCredentialsTokenSource::class)
	);
};

$container[DelayedReviewSender::class] = function (Container $container) {
	return new DelayedReviewSender(
		$container->get(DeloreanClient::class),
		$container->get('em')
	);
};

/**
 * LOGGER
 * @param $c
 * @return Logger
 */
$container['logger'] = function (Container $c) {

	$settings = $c->get('settings');
	$logger   = new Logger($settings['logger']['name']);
	$logger->pushProcessor(new UidProcessor());

	$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

	// papertrail logging
	$output    = "%channel%.%level_name%: %message%";
	$formatter = new LineFormatter($output);

	$syslogHandler = new SyslogUdpHandler(
		"logs3.papertrailapp.com",
		29915,
		LOG_USER,
		Logger::DEBUG,
		true,
		getenv('log_name') ? getenv('log_name') : 'unknown-backend'
	);
	$syslogHandler->setFormatter($formatter);
	$logger->pushHandler($syslogHandler);

	return $logger;
};

$container['errorHandler'] = function (Container $c) {
	return new \App\Handlers\Error($c->get('logger'));
};

/**
 * LIBRARIES
 */

$container['view'] = function (Container $c) {

	$templateDir = $c->get('settings')['template'];
	$loader      = new Twig_Loader_Filesystem(__DIR__ . $templateDir);
	$view        = new Twig_Environment($loader);
	$view->addExtension(new TwigFilters());

	return $view;
};

/**
 * DATABASES
 * @param $c
 * @return mixed
 */

$container['firebase'] = function (Container $c) {
	$firebase = (new Factory())
		->withCredentials(__DIR__ . '/google-service-account.json')
		->create();

	return $firebase;
};

$container[LocationProvider::class] = function (Container $container) {
	return new LocationProvider(
		$container->get('em')
	);
};

$container[Database::class] = function (Container $c) {
	$fb = $c->get('firebase');

	return $fb->getDatabase();
};

$container['mysql'] = function (Container $c) {

	$settings = $c->get('settings')['pdo'];

	return new PDO($settings['dsn'], $settings['username'], $settings['password']);
};

$container['pdo'] = function (Container $c) {
	$settings = $c->get('settings')['pdo'];

	return new PDO($settings['dsn'], $settings['username'], $settings['password']);
};

$container[EmailSignupService::class] = function (Container $container) {
	return new EmailSignupService(
		$container->get('em'),
		$container->get(Registrations\_RegistrationsController::class),
		$container->get(_ClientsController::class)
	);
};

$container[Router::class] = function (Container $container) {
	$router = RouterSingleton::getRouter()
		->register(new LoggingNotifier($container->get('logger')))
		->register(new EmailNotifier($container->get(Mail\MailSender::class)), LoyaltyStampCardEvent::TYPE_STAMP)
		->register(new InAppNotificationNotifier($container->get(InAppNotification::class)), LoyaltyStampCardEvent::TYPE_STAMP);

	if (extension_loaded('newrelic')) {
		$router->register(new NewRelicNotifier());
	}
	return $router;
};

$container[EventNotifier::class] = function (Container $container) {
	return $container->get(Router::class);
};

$container[OrganizationLoyaltyServiceFactory::class] = function (Container $container) {
	return new OrganizationLoyaltyServiceFactory(
		$container->get('em'),
		$container->get(EventNotifier::class)
	);
};

$container[OrganizationStampCardController::class] = function (Container $container) {
	return new OrganizationStampCardController(
		$container->get(OrganizationProvider::class),
		$container->get(OrganizationLoyaltyServiceFactory::class),
		$container->get('em'),
		$container->get(UserProvider::class)
	);
};

$container[EmailSignupController::class] = function (Container $container) {
	return new EmailSignupController(
		$container->get(ProfileInteractionFactory::class),
		$container->get(OrganizationProvider::class),
	);
};

$container['em'] = function (Container $c) {
	$classLoader = new ClassLoader('DoctrineExtensions', '/path/to/extensions');
	$classLoader->register();
	$settings = $c->get('settings')['doctrine'];
	$config   = Setup::createAnnotationMetadataConfiguration(
		$settings['meta']['entity_path'],
		$settings['meta']['auto_generate_proxies'],
		$settings['meta']['proxy_dir'],
		$settings['meta']['cache'],
		false
	);

	Type::addType('uuid', UuidType::class);

	$config->addCustomDatetimeFunction('TIMESTAMPDIFF', TimestampDiff::class);
	$config->addCustomDatetimeFunction('NOW', Now::class);
	$config->addCustomDatetimeFunction('MINUTE', Minute::class);
	$config->addCustomDatetimeFunction('DATESUB', DateSub::class);
	$config->addCustomDatetimeFunction('YEAR', Year::class);
	$config->addCustomDatetimeFunction('MONTH', Month::class);
	$config->addCustomDatetimeFunction('DAY', Day::class);
	$config->addCustomDatetimeFunction('HOUR', Hour::class);
	$config->addCustomDatetimeFunction('WEEK', Week::class);
	$config->addCustomDatetimeFunction('UNIX_TIMESTAMP', UnixTimestamp::class);
	$config->addCustomDatetimeFunction('DATE', Date::class);
	$config->addCustomDatetimeFunction('DATE_FORMAT', DateFormat::class);
	$config->addCustomDatetimeFunction('DAYOFWEEK', DayOfWeek::class);
	$config->addCustomDatetimeFunction('ROUND', Round::class);
	$config->addCustomDatetimeFunction('TIME_TO_SEC', TimeToSec::class);
	$config->addCustomDatetimeFunction('FROM_UNIXTIME', FromUnixtime::class);
	$config->addCustomNumericFunction('CHAR_LENGTH', CharLength::class);
	$config->addCustomStringFunction('GROUP_CONCAT', GroupConcat::class);
	$config->addCustomStringFunction('STR_TO_DATE', StrToDate::class);
	$config->addCustomStringFunction('MATCH_AGAINST', MatchAgainst::class);
	$config->addCustomStringFunction('IF', IfElse::class);
	$config->addCustomStringFunction('IFNULL', \DoctrineExtensions\Query\Mysql\IfNull::class);
	return \Doctrine\ORM\EntityManager::create($settings['connection'], $config);
};

/**
 * Caches
 */
$container['marketingCache'] = function () {
	return new CacheEngine(getenv('MARKETING_REDIS'));
};

$container['connectCache'] = function () {
	return new CacheEngine(getenv('CONNECT_REDIS'));
};

$container['infrastructureCache'] = function () {
	return new CacheEngine(getenv('INFRASTRUCTURE_REDIS'));
};

$container['nearlyCache'] = function () {
	return new CacheEngine(getenv('NEARLY_REDIS'));
};

/**
 * @param $c
 * @return Server
 */

$container['oAuth'] = function (Container $c) {
	$storage = new App\DataAccess\_oAuth2_CustomStorage($c->get('pdo'));
	$req     = $c->request->getParsedBody();
	if ($req === null) {
		$req = [];
	}
	$storage->setRequest($req);
	$config = [
		'always_issue_new_refresh_token' => true,
		'access_lifetime'                => 86400,
		'refresh_token_lifetime'         => 31536000,
		'auth_code_lifetime'             => 600,
	];
	// Pass a storage object or array of storage objects to the OAuth2 server class
	$server = new OAuth2\Server($storage, $config);

	// add grant types
	$server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));
	$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
	$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
	$server->addGrantType(
		new OAuth2\GrantType\RefreshToken(
			$storage,
			['always_issue_new_refresh_token' => true]
		)
	);

	return $server;
};

$container[\App\Policy\CookieMiddleware::class] = function () {
	return new \App\Policy\CookieMiddleware();
};

$container[\App\Package\WebTracking\Tracking::class] = function (Container $c) {
	return new \App\Package\WebTracking\Tracking(
		$c->get('em'),
		$c->get(ProfileInteractionFactory::class)
	);
};

$container[GiftCardService::class] = function (Container $c) {
	return new GiftCardService(
		$c->get('em'),
		$c->get(ProfileInteractionFactory::class)
	);
};

$container[SegmentMarketingController::class] = function (Container $container) {
	return new SegmentMarketingController(
		new CampaignSenderFactory(
			new SegmentRepositoryFactory(
				$container->get('em'),
				$container->get(QueryFactory::class),
				$container->get(OrganizationProvider::class)
			),
			new SNSNotifier(
				(new SNSConfig())->client(),
				getenv('SEGMENT_MARKETING_SNS_ARN') ? getenv('SEGMENT_MARKETING_SNS_ARN') : 'arn:aws:sns:eu-west-1:354378566817:segment-marketing'
			)
		)
	);
};

$container[GiftCardController::class] = function (Container $c) {
	return new GiftCardController(
		$c->get(GiftCardService::class),
		$c->get('em'),
		$c->get(Mail\MailSender::class),
		$c->get(UserProvider::class),
		$c->get(UserRoleChecker::class)
	);
};

$container[LocationAccessChangeRequestProvider::class] = function (Container $c) {
	return new LocationAccessChangeRequestProvider($c->get('em'));
};

$container[LocationService::class] = function (Container $c) {
	return new LocationService($c->get('em'));
};

$container[Mail\_MailController::class] = function (Container $c) {
	return new Mail\_MailController($c->get('em'));
};

$container[_oAuth2Controller::class] = function (Container $c) {
	return new _oAuth2Controller(
		$c->get('logger'),
		$c->get('oAuth'),
		$c->get('em'),
		$c->get(UserRoleChecker::class),
	);
};

$container[_GoogleOauthClient::class] = function (Container $c) {
	return new _GoogleOauthClient($c->get('oAuth'), $c->get('em'));
};

$container[_MagicLink::class] = function (Container $c) {
	return new _MagicLink($c->get('em'), $c->get('oAuth'));
};

// oAuth Controller for retrieving tokens
$container[_oAuth2TokenController::class] = function (Container $c) {
	return new _oAuth2TokenController($c->get('oAuth'), $c->get('em'));
};

$container[ProfileChecker::class] = function (Container $c) {
	return new ProfileChecker($c->get('em'), $c->get('oAuth'));
};

$container[QueryFactory::class] = function (Container $container) {
	return new QueryFactory($container->get('em'));
};

$container[SegmentController::class] = function (Container $container) {
	return new SegmentController(
		$container->get('em'),
		$container->get(QueryFactory::class),
		$container->get(OrganizationProvider::class)
	);
};

/**
 * POLICIES
 */

$container[Auth::class] = function (Container $c) {
	return new App\Policy\Auth($c->get(_oAuth2Controller::class), $c->get('em'));
};

$container[InteractionService::class] = function (Container $container) {
	return new InteractionService(
		$container->get('em')
	);
};

$container[InteractionController::class] = function (Container $container) {
	$redirectUrl = getenv('CHECKOUT_REDIRECT_HOST');
	if ($redirectUrl === false) {
		$redirectUrl = "https://my.stampede.ai/checkout";
	}
	return new InteractionController(
		$container->get(InteractionService::class),
		$container->get(LocationService::class),
		$redirectUrl
	);
};

$container[OrganizationSettingsService::class] = function (Container $container) {
	return new OrganizationSettingsService(
		$container->get('em')
	);
};

$container[OrganizationSettingsController::class] = function (Container $container) {
	return new OrganizationSettingsController(
		$container->get(OrganizationSettingsService::class),
		$container->get(OrganizationProvider::class)
	);
};

$container[OrgTypeMiddleware::class] = function (Container $c) {
	return new OrgTypeMiddleware(
		$c->get(UserRoleChecker::class),
		$c->get(UserProvider::class)
	);
};

$container[App\Policy\GetSerialAdmin::class] = function (Container $c) {
	return new App\Policy\GetSerialAdmin($c->get(LocationService::class));
};

$container[UserRoleChecker::class] = function (Container $c) {
	return new UserRoleChecker($c->get('em'));
};

$container[App\Policy\getSerials::class] = function (Container $c) {
	return new App\Policy\getSerials(
		$c->get(UserRoleChecker::class),
		$c->get(UserProvider::class)
	);
};

$container[ResourceRoleMiddleware::class] = function (Container $c) {
	return new ResourceRoleMiddleware($c->get(UserProvider::class), $c->get(UserRoleChecker::class));
};

$container[NearlyLogInService::class] = function (Container $c) {
	return new NearlyLogInService($c->get(_oAuth2Controller::class), $c->get('em'));
};

/**
 * REPOSITORIES
 */
$container[UserRegistrationRepository::class] = function (Container $c) {
	return new UserRegistrationRepository($c->get('logger'), $c->get('em'));
};

/**
 * CONTROLLERS
 * @param Container $container
 * @return ProfileLoyaltyServiceFactory
 */

$container[ProfileLoyaltyServiceFactory::class] = function (Container $container) {
	return new ProfileLoyaltyServiceFactory(
		$container->get(RawStatementExecutor::class),
		$container->get('em'),
		$container->get(EventNotifier::class)
	);
};

$container[RawStatementExecutor::class] = function (Container $container) {
	return new RawStatementExecutor($container->get('em'));
};

$container[UserProfileProvider::class] = function (Container $container) {
	return new UserProfileProvider($container->get('em'));
};

$container[StampContextFactory::class] = function (Container $container) {
	return new StampContextFactory($container->get('em'));
};

$container[AppStampCardController::class] = function (Container $container) {
	$container->get(ProfileInteractionFactory::class); // a hack
	return new AppStampCardController(
		$container->get(UserProfileProvider::class),
		$container->get(ProfileLoyaltyServiceFactory::class),
		$container->get('em'),
		$container->get(StampContextFactory::class)
	);
};

$container[OrganizationsController::class] = function (Container $c) {
	return new OrganizationsController(
		$c->get('logger'),
		$c->get(OrganizationService::class),
		$c->get(UserProvider::class),
		$c->get('em'),
		$c->get(LocationService::class),
		$c->get(MemberService::class)
	);
};

$container[HealthcheckController::class] = function (Container $c) {
	return new HealthcheckController(
		$c->get('logger'),
		$c->get('em')
	);
};

$container[SubscriptionCreator::class] = function (Container $c) {
	$isChargebeeEnabled = $c->get('settings')['chargebee']['enabled'];
	$entityManager      = $c->get('em');
	if ($isChargebeeEnabled) {
		return $c->get(LocationSubscriptionController::class);
	}

	return new DummySubscriptionCreator($entityManager);
};

$container[QuoteCreator::class] = function (Container $c) {
	return new _QuotesController(
		$c->get('em'),
		$c->get(OrganizationService::class),
		$c->get(SubscriptionCreator::class)
	);
};

$container[_MembersController::class] = function (Container $c) {
	return new _MembersController(
		$c->get('oAuth'),
		$c->get('em'),
		$c->get(_oAuth2Controller::class),
		$c->get(LocationAccessChangeRequestProvider::class),
		$c->get(LocationService::class),
		$c->get(MemberService::class),
		$c->get(QuoteCreator::class)
	);
};

$container[_LocationsController::class] = function (Container $c) {
	return new _LocationsController($c->get('em'));
};

$container[_LocationSettingsController::class] = function (Container $c) {
	return new _LocationSettingsController($c->get('em'));
};

$container[_LocationsDevicesController::class] = function (Container $c) {
	return new _LocationsDevicesController($c->get('em'));
};

$container[Registrations\_RegistrationsController::class] = function (Container $c) {
	return new Registrations\_RegistrationsController($c->get('em'));
};

$container[Registrations\_ValidationController::class] = function (Container $c) {
	return new Registrations\_ValidationController($c->get('em'));
};

$container[_LegacyAuthController::class] = function (Container $c) {
	return new _LegacyAuthController($c->get('em'));
};

$container[Pricing\_LocationPlanController::class] = function (Container $c) {
	return new Pricing\_LocationPlanController($c->get('em'));
};

$container[App\Controllers\Branding\_BrandingController::class] = function (Container $c) {
	return new App\Controllers\Branding\_BrandingController($c->get('em'));
};

$container[Integrations\UniFi\_UniFiController::class] = function (Container $c) {
	return new Integrations\UniFi\_UniFiController($c->get('em'));
};

$container[Integrations\Mikrotik\_MikrotikController::class] = function (Container $c) {
	return new Integrations\Mikrotik\_MikrotikController($c->get('em'));
};

$container[Integrations\Mikrotik\_MikrotikUserDataController::class] = function (Container $c) {
	return new Integrations\Mikrotik\_MikrotikUserDataController($c->get('em'));
};

$container[Integrations\Mikrotik\_MikrotikInformController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Mikrotik\_MikrotikInformController($c->get('em'));
};

$container[Integrations\Radius\_RadiusController::class] = function (Container $c) {
	return new Integrations\Radius\_RadiusController($c->get('em'));
};

$container[Integrations\OpenMesh\_OpenMeshInformController::class] = function (Container $c) {
	return new App\Controllers\Integrations\OpenMesh\_OpenMeshInformController($c->get('em'));
};

$container[Integrations\Xero\_XeroController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Xero\_XeroController($c->get('em'), $c->get(XeroOAuth::class));
};

$container[Integrations\Stripe\_StripeController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Stripe\_StripeController($c->get('logger'), $c->get('em'));
};

$container[Integrations\Stripe\_StripeCustomerController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Stripe\_StripeCustomerController($c->get('logger'), $c->get('em'));
};

$container[Integrations\Stripe\_StripeCardsController::class] = function (Container $c) {
	return new Integrations\Stripe\_StripeCardsController($c->get('logger'), $c->get('em'));
};

$container[Pricing\_LocationPaymentMethodController::class] = function (Container $c) {
	return new Pricing\_LocationPaymentMethodController($c->get("em"));
};

$container[Integrations\Stripe\_StripeChargeController::class] = function (Container $c) {
	return new Integrations\Stripe\_StripeChargeController($c->get('logger'), $c->get('em'));
};

$container[App\Controllers\Payments\_PaymentsController::class] = function (Container $c) {
	return new App\Controllers\Payments\_PaymentsController($c->get('logger'), $c->get('em'), $c->get('mixpanel'));
};

$container[App\Controllers\Migrations\_MigrationsController::class] = function (Container $c) {
	return new App\Controllers\Migrations\_MigrationsController($c->get('em'));
};

$container[_NearlyController::class] = function (Container $c) {
	return new _NearlyController($c->get('logger'), $c->get('em'));
};

$container[NearlyController::class] = function (Container $c) {
	return new NearlyController($c->get('em'), $c->get('logger'), $c->get(NearlyAuthentication::class), $c->get(ReviewService::class));
};

$container[NearlyAuthentication::class] = function (Container $c) {
	return new NearlyAuthentication($c->get('em'), $c->get(ProfileInteractionFactory::class));
};

$container[_NearlyDevicesController::class] = function (Container $c) {
	return new _NearlyDevicesController($c->get('logger'), $c->get('em'));
};

$container[_ClientsController::class] = function (Container $c) {
	return new _ClientsController($c->get('em'));
};

$container[_ClientsUpdateController::class] = function (Container $c) {
	return new _ClientsUpdateController($c->get('em'));
};

$container[_ClientsActiveController::class] = function (Container $c) {
	return new _ClientsActiveController($c->get('em'));
};

$container[_PasswordController::class] = function (Container $c) {
	return new App\Controllers\Auth\_PasswordController($c->get('em'));
};

$container[_StripeSubscriptionsController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Stripe\_StripeSubscriptionsController($c->get('logger'), $c->get('em'));
};

$container[_StripeSubscriptionsItemsController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Stripe\_StripeSubscriptionsItemsController($c->get('logger'), $c->get('em'));
};

$container[MeRepository::class] = function ($c) {
	return new MeRepository($c->get(UserRoleChecker::class), $c->get('em'));
};

$container[MeController::class] = function (Container $c) {
	return new MeController(
		$c->get(UserProvider::class),
		$c->get(MeRepository::class)
	);
};

$container[_QuotesController::class] = function (Container $c) {
	return new _QuotesController($c->get('em'), $c->get(OrganizationService::class), $c->get(LocationSubscriptionController::class));
};

$container[_SubscriptionPlanController::class] = function (Container $c) {
	return new App\Controllers\Billing\Subscriptions\_SubscriptionPlanController($c->get('em'));
};

$container[_MarketingLegacy::class] = function (Container $c) {
	return new App\Controllers\Marketing\_MarketingLegacy($c->get('logger'), $c->get('em'), $c->get('marketingCache'), $c->get(UserRegistrationRepository::class), new QueueSender());
};

$container[_MarketingReportController::class] = function (Container $c) {
	return new _MarketingReportController($c->get('em'));
};

$container[_WiFiController::class] = function (Container $c) {
	return new _WiFiController($c->get('em'));
};

$container[_BandwidthController::class] = function (Container $c) {
	return new _BandwidthController($c->get('em'));
};

$container[_TimeoutsController::class] = function (Container $c) {
	return new _TimeoutsController($c->get('em'));
};

$container[_WhitelistController::class] = function (Container $c) {
	return new _WhitelistController($c->get('em'));
};

$container[_QueueController::class] = function (Container $c) {
	return new _QueueController($c->get('em'));
};

$container[_LocationReportController::class] = function (Container $c) {
	return new _LocationReportController($c->get('em'));
};

$container[_SuperController::class] = function (Container $c) {
	return new _SuperController($c->get('em'));
};

$container[_ConnectedController::class] = function (Container $c) {
	return new _ConnectedController($c->get('em'));
};

$container[_DeformController::class] = function (Container $c) {
	return new _DeformController($c->get('em'));
};

$container[_ValidationTimeoutsController::class] = function (Container $c) {
	return new _ValidationTimeoutsController($c->get('em'));
};

$container[_LogoUploadController::class] = function (Container $c) {
	return new _LogoUploadController($c->get('em'));
};

$container[_UploadStorageController::class] = function (Container $c) {
	return new _UploadStorageController($c->get('em'));
};

$container[_MikrotikFacebookController::class] = function (Container $c) {
	return new App\Controllers\Integrations\Mikrotik\_MikrotikFacebookController($c->get('em'));
};

$container[_LocationNetworkSettings::class] = function (Container $c) {
	return new App\Controllers\Locations\Settings\_LocationNetworkSettings($c->get('em'));
};

$container[_PostCodeBuilder::class] = function (Container $c) {
	return new App\Controllers\Schedule\_PostCodeBuilder($c->get('em'));
};

$container[_IgniteNetController::class] = function (Container $c) {
	return new App\Controllers\Integrations\IgniteNet\_IgniteNetController($c->get('em'));
};

$container[_IgniteNetInformController::class] = function (Container $c) {
	return new App\Controllers\Integrations\IgniteNet\_IgniteNetInformController($c->get('em'));
};

$container[_EmailReports::class] = function (Container $c) {
	return new _EmailReports($c->get('em'));
};

$container[_UserController::class] = function (Container $c) {
	return new App\Controllers\User\_UserController($c->get('em'));
};

$container[_AudienceController::class] = function (Container $c) {
	return new _AudienceController($c->get('em'));
};

$container[_PartnerBranding::class] = function (Container $c) {
	return new _PartnerBranding($c->get('em'));
};

$container[_MarketingCallBackController::class] = function (Container $c) {
	return new _MarketingCallBackController($c->get('em'));
};

$container[_SMSController::class] = function (Container $c) {
	return new _SMSController($c->get('em'));
};

$container[Integrations\Hooks\_HooksController::class] = function (Container $c) {
	return new Integrations\Hooks\_HooksController($c->get('em'));
};

$container[App\Controllers\Integrations\ChargeBee\_ChargeBeeHandleErrors::class] = function (Container $c) {
	return new App\Controllers\Integrations\ChargeBee\_ChargeBeeHandleErrors();
};

$container[Mail\MailSender::class] = function (Container $c) {
	return new Mail\_MailController($c->get('em'));
};

$container[InAppNotification::class] = function (Container $c) {
	return new InAppNotification($c->get('em'));
};

$container[CancellationController::class] = function (Container $c) {
	$emails = $c->get('settings')['cancellationEmails'];

	return new CancellationController(
		$c->get(Mail\MailSender::class),
		$c->get(UserProvider::class),
		$emails
	);
};

$container[ChargeBeeEventGetter::class] = function (Container $c) {
	$chargebeeSettings  = $c->get('settings')['chargebee'];
	$isChargebeeEnabled = $chargebeeSettings['enabled'];
	if ($isChargebeeEnabled) {
		return new _ChargeBeeEventController();
	}

	return new StubEventGetter();
};

$container[View::class] = function (Container $c) {
	return new OverviewView(
		new ConnectionsView($c->get('em')),
		new ImpressionsView($c->get('em')),
		new UsersView($c->get('em')),
		new ReviewsView($c->get('em'))
	);
};

$container[UserProvider::class] = function (Container $c) {
	return new UserProvider($c->get('em'));
};

$container[OverviewController::class] = function (Container $c) {
	return new OverviewController(
		$c->get(View::class),
		$c->get(UserProvider::class),
		$c->get(OrganizationService::class)
	);
};

$container[SMSTransactions::class] = function (Container $c) {
	return new SMSTransactions(
		$c->get(OrganizationService::class),
		$c->get('em')
	);
};

$container[ChargeBeeWebHookController::class] = function (Container $c) {
	return new ChargeBeeWebHookController(
		$c->get('em'),
		$c->get(Mail\_MailController::class),
		$c->get(ChargeBeeEventGetter::class),
		$c->get(LocationSubscriptionController::class),
		$c->get(SMSTransactions::class)
	);
};

$container[App\Controllers\Integrations\ChargeBee\_ChargeBeeSubscriptionController::class] = function (Container $c) {
	return new App\Controllers\Integrations\ChargeBee\_ChargeBeeSubscriptionController();
};

$container[Integrations\ChargeBee\_ChargeBeePaymentSourceController::class] = function (Container $c) {
	return new Integrations\ChargeBee\_ChargeBeePaymentSourceController($c->get('em'));
};

$container[_ChargeBeeCustomerController::class] = function (Container $c) {
	return new _ChargeBeeCustomerController($c->get('em'));
};

$container[Integrations\GoCardless\_GoCardlessController::class] = function (Container $c) {
	return new Integrations\GoCardless\_GoCardlessController($c->get('em'));
};

$container[_FeatureRequestNotifyController::class] = function (Container $c) {
	return new _FeatureRequestNotifyController($c->get('em'));
};

$container[_ReleaseNotifyController::class] = function (Container $c) {
	return new _ReleaseNotifyController($c->get('em'));
};

$container[MenuGenerator::class] = function (Container $c) {
	return new App\Controllers\Locations\MenuGenerator\MenuGenerator($c->get('em'));
};

$container[QuoteScheduler::class] = function (Container $c) {
	return new QuoteScheduler($c->get('em'));
};

$container[_PreviewCampaignController::class] = function (Container $c) {
	return new _PreviewCampaignController($c->get('em'));
};

$container[_PartnerNetRevenue::class] = function (Container $c) {
	return new _PartnerNetRevenue($c->get('em'));
};

$container[_GalleryController::class] = function (Container $c) {
	return new _GalleryController($c->get('em'), $c->get('logger'));
};

$container[_NotificationSettingsController::class] = function (Container $c) {
	return new _NotificationSettingsController($c->get('em'));
};

$container[PushNotifications::class] = function (Container $c) {
	return new PushNotifications($c->get('em'));
};

$container[_GeoInfoViaIP::class] = function (Container $c) {
	return new _GeoInfoViaIP($c->get('em'));
};

$container[_NotificationsSendType::class] = function (Container $c) {
	return new _NotificationsSendType($c->get('em'));
};

$container[hasNotificationsSubs::class] = function (Container $c) {
	return new hasNotificationsSubs($c->get('em'));
};


$container[Integrations\PayPal\_PayPalController::class] = function (Container $c) {
	return new Integrations\PayPal\_PayPalController($c->get('em'));
};

$container[_ChangelogController::class] = function (Container $c) {
	return new _ChangelogController($c->get('em'));
};

$container[_BaseTemplateController::class] = function (Container $c) {
	return new _BaseTemplateController($c->get('em'));
};


$container[_MarketingUserGroupController::class] = function (Container $c) {
	return new _MarketingUserGroupController($c->get('em'));
};

$container[_LocationCreationController::class] = function (Container $c) {
	return new _LocationCreationController($c->get('em'));
};

$container[_LocationScheduleController::class] = function (Container $c) {
	return new _LocationScheduleController($c->get('em'));
};

$container[BrandingController::class] = function (Container $c) {
	return new BrandingController($c->get('em'));
};

$container[Debugger::class] = function (Container $c) {
	return new Debugger($c->get('em'));
};

$container[PredictConnectionsReportController::class] = function (
	$c
) {
	return new App\Controllers\Locations\Reports\PredictiveReports\PredictConnectionsReportController($c->get('em'));
};

$container[UserOverviewController::class] = function (Container $c) {
	$em         = $c->get('em');
	$userFilter = new UserFilter($em);

	return new UserOverviewController($em, $userFilter);
};

$container[Integrations\OpenMesh\OpenMeshNearlySettings::class] = function (Container $c) {
	return new Integrations\OpenMesh\OpenMeshNearlySettings(
		$c->get('em')
	);
};

$container[Integrations\Radius\RadiusNearlySettings::class] = function (Container $c) {
	return new Integrations\Radius\RadiusNearlySettings($c->get('em'));
};

$container[LocationFacebookController::class] = function (Container $c) {
	return new LocationFacebookController($c->get('em'));
};

$container[EmailValidator::class] = function (Container $c) {
	return new EmailValidator($c->get('em'));
};

$container[NearlyAuthController::class] = function (Container $c) {
	return new NearlyAuthController($c->get('em'));
};

$container[_GeneralController::class] = function (Container $c) {
	return new _GeneralController($c->get('em'));
};

$container[_CaptureController::class] = function (Container $c) {
	return new _CaptureController($c->get('em'));
};

$container[NearlyProfileController::class] = function (Container $c) {
	return new NearlyProfileController($c->get('em'));
};

$container[LocationOtherController::class] = function (Container $c) {
	return new LocationOtherController($c->get('em'));
};

$container[LocationPositionController::class] = function (Container $c) {
	return new LocationPositionController($c->get('em'));
};

$container[Subscriptions\FailedTransactionController::class] = function (Container $c) {
	return new Subscriptions\FailedTransactionController($c->get('em'));
};

$container['mixpanel'] = function (Container $c) {
	return new _Mixpanel();
};

$container[NearlyPayPalController::class] = function (Container $c) {
	return new NearlyPayPalController($c->get('logger'), $c->get('em'), $c->get('nearlyCache'), $c->get('mixpanel'), $c->get(NearlyAuthentication::class));
};

$container[LogoutController::class] = function (Container $c) {
	return new LogoutController($c->get('em'));
};

$container[FakeConnectionReportController::class] = function (Container $c) {
	return new FakeConnectionReportController($c->get('em'));
};

$container[FakeCustomerReportController::class] = function (Container $c) {
	return new FakeCustomerReportController($c->get('em'));
};

$container[FakeDeviceReportController::class] = function (Container $c) {
	return new FakeDeviceReportController($c->get('em'));
};

$container[FakePaymentsReportController::class] = function (Container $c) {
	return new FakePaymentsReportController($c->get('em'));
};

$container[FakeRegistrationsReportController::class] = function (Container $c) {
	return new FakeRegistrationsReportController($c->get('em'));
};



$container[LocationSubscriptionController::class] = function (Container $c) {
	return new LocationSubscriptionController(
		$c->get('em'),
		$c->get(OrganizationService::class),
		$c->get(UserProvider::class),
		$c->get(_ChargeBeeCustomerController::class)
	);
};

$container[URLShortenerController::class] = function (Container $c) {
	return new URLShortenerController($c->get('em'));
};

$container[URLShortenerEventController::class] = function (Container $c) {
	return new URLShortenerEventController($c->get('em'));
};

$container[RemoveIncompleteCampaigns::class] = function (Container $c) {
	return new RemoveIncompleteCampaigns($c->get('em'));
};

$container[RandomOptOutCodeGenerator::class] = function (Container $c) {
	return new RandomOptOutCodeGenerator();
};

$container[LocationsInfoController::class] = function (Container $c) {
	return new LocationsInfoController($c->get('em'));
};

$container[OrganizationService::class] = function (Container $c) {
	return new OrganizationService($c->get('em'));
};

$container[MemberValidationController::class] = function ($c) {
	return new MemberValidationController($c->get('em'));
};

$container[_PasswordController::class] = function ($c) {
	return new _PasswordController($c->get('em'));
};

$container[MemberService::class] = function ($c) {
	return new MemberService(
		$c->get('em'),
		$c->get(MemberValidationController::class),
		$c->get(_PasswordController::class),
		$c->get(OrganizationService::class),
		$c->get(UserRoleChecker::class)
	);
};

$container[ResellerOrganisationService::class] = function ($c) {
	return new ResellerOrganisationService(
		$c->get(MemberService::class),
		$c->get(OrganizationService::class),
		$c->get(_ChargeBeeCustomerController::class),
		$c->get('em')
	);
};

$container[PartnerCustomerController::class] = function ($c) {
	return new PartnerCustomerController($c->get(OrganizationService::class), $c->get(ResellerOrganisationService::class));
};

$container[CustomerPricingController::class] = function (Container $c) {
	return new CustomerPricingController($c->get('em'));
};

$container[NearlyProfileAccountService::class] = function (Container $c) {
	return new NearlyProfileAccountService($c->get('em'));
};

$container[App\Controllers\Nearly\NearlyProfile\NearlyOptOut::class] = function (Container $c) {
	return new App\Controllers\Nearly\NearlyProfile\NearlyOptOut($c->get('logger'), $c->get('em'), $c->get(UserRegistrationRepository::class), new QueueSender());
};

$container[NearlyProfileDownloadController::class] = function (Container $c) {
	return new NearlyProfileDownloadController($c->get('em'));
};

$container[\App\Controllers\Nearly\NearlyProfileOptOut::class] = function (Container $c) {
	return new \App\Controllers\Nearly\NearlyProfileOptOut($c->get('em'));
};

$container[BrandingReportController::class] = function (Container $c) {
	return new BrandingReportController($c->get('em'));
};

$container[GeneralReportController::class] = function (Container $c) {
	return new GeneralReportController($c->get('em'));
};

$container[CaptureReportController::class] = function (Container $c) {
	return new CaptureReportController($c->get('em'));
};

$container[MultiSiteReportProducer::class] = function (Container $c) {
	return new MultiSiteReportProducer($c->get('em'));
};

$container[MultiSiteReportController::class] = function (Container $c) {
	return new MultiSiteReportController($c->get('em'));
};

$container[ReportController::class] = function (Container $c) {
	return new ReportController($c->get('em'));
};

$container[Integrations\RabbitMQ\FileExportWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\FileExportWorker($c->get(Mail\_MailController::class), $c->get('em'));
};

$container[Integrations\RabbitMQ\SyncDevicesWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\SyncDevicesWorker($c->get('em'));
};

$container[Integrations\RabbitMQ\ZapierWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\ZapierWorker($c->get('em'));
};

$container[LocationTypeController::class] = function (Container $c) {
	return new LocationTypeController($c->get('em'));
};

$container[LocationTypeSerialController::class] = function (Container $c) {
	return new LocationTypeSerialController($c->get('em'));
};

$container[LocationTypeSerialReportController::class] = function (Container $c) {
	return new LocationTypeSerialReportController($c->get('em'));
};

$container[Integrations\RabbitMQ\NotificationWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\NotificationWorker($c->get('em'));
};

$container[Integrations\RabbitMQ\EmailValidationWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\EmailValidationWorker($c->get(Mail\_MailController::class), $c->get('em'));
};

$container[MemberWorthController::class] = function (Container $c) {
	return new MemberWorthController($c->get('em'));
};

$container[Integrations\RabbitMQ\GDPRNotifierWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\GDPRNotifierWorker($c->get('em'));
};

$container[_MarketingRunner::class] = function (Container $c) {
	return new _MarketingRunner($c->get('em'));
};

$container[LocationPolicyController::class] = function (Container $c) {
	return new LocationPolicyController($c->get('em'));
};

$container[NearlyGDPRCompliance::class] = function (Container $c) {
	return new NearlyGDPRCompliance($c->get('logger'), $c->get('em'), $c->get(UserRegistrationRepository::class));
};

$container[Integrations\RabbitMQ\OptOutWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\OptOutWorker($c->get('logger'), $c->get('em'), $c->get(UserRegistrationRepository::class), $c->get(NearlyProfileOptOut::class));
};

$container[Integrations\RabbitMQ\InformWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\InformWorker($c->get('em'));
};

$container[LocationTemplateController::class] = function (Container $c) {
	return new LocationTemplateController($c->get('em'));
};

$container[LocationReviewController::class] = function (Container $c) {
	return new LocationReviewController($c->get('em'));
};

$container[ReviewSchedule::class] = function (Container $c) {
	return new ReviewSchedule($c->get('em'));
};

$container[LocationReviewController::class] = function (Container $c) {
	return new LocationReviewController($c->get('em'));
};

$container[LocationReviewTimelineController::class] = function (Container $c) {
	return new LocationReviewTimelineController($c->get('em'));
};

$container[Integrations\Google\PlaceIDVerifierController::class] = function (Container $c) {
	return new Integrations\Google\PlaceIDVerifierController($c->get('em'));
};

$container[Integrations\Facebook\_FacebookLoginController::class] = function (Container $c) {
	return new Integrations\Facebook\_FacebookLoginController($c->get('em'));
};

$container[Integrations\Facebook\_FacebookPagesController::class] = function (Container $c) {
	return new Integrations\Facebook\_FacebookPagesController($c->get('em'));
};

$container[Integrations\TripAdvisor\TripAdvisorReviewController::class] = function (Container $c) {
	return new Integrations\TripAdvisor\TripAdvisorReviewController($c->get('em'));
};

$container[FirebaseCloudMessagingController::class] = function (Container $c) {
	return new FirebaseCloudMessagingController($c->get('em'));
};

$container[Integrations\Textlocal\TextLocalContactController::class] = function (Container $c) {
	return new Integrations\Textlocal\TextLocalContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[Integrations\Textlocal\TextLocalGroupController::class] = function (Container $c) {
	return new Integrations\Textlocal\TextLocalGroupController($c->get('em'));
};

$container[Integrations\Textlocal\TextLocalSetupController::class] = function (Container $c) {
	return new Integrations\Textlocal\TextLocalSetupController($c->get('em'));
};

$container[TextLocalSendWorker::class] = function (Container $c) {
	return new TextLocalSendWorker($c->get('logger'), $c->get('em'), $c->get(TextLocalContactController::class));
};

$container[MailChimpContactController::class] = function (Container $c) {
	return new MailChimpContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[Integrations\MailChimp\MailChimpListController::class] = function (Container $c) {
	return new Integrations\MailChimp\MailChimpListController($c->get('em'));
};

$container[Integrations\MailChimp\MailChimpSetupController::class] = function (Container $c) {
	return new Integrations\MailChimp\MailChimpSetupController($c->get('em'));
};

$container[MailChimpSendWorker::class] = function (Container $c) {
	return new MailChimpSendWorker($c->get('logger'), $c->get('em'), $c->get(MailChimpContactController::class));
};

$container[Integrations\dotMailer\DotMailerAddressBookController::class] = function (Container $c) {
	return new Integrations\dotMailer\DotMailerAddressBookController($c->get('em'));
};

$container[DotMailerContactController::class] = function (Container $c) {
	return new DotMailerContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[Integrations\dotMailer\DotMailerSetupController::class] = function (Container $c) {
	return new Integrations\dotMailer\DotMailerSetupController($c->get('em'));
};

$container[Integrations\RabbitMQ\DotMailerSendWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\DotMailerSendWorker($c->get('logger'), $c->get('em'), $c->get(DotMailerContactController::class));
};

$container[FilterListController::class] = function (Container $c) {
	return new FilterListController($c->get('logger'), $c->get('em'), new UserFilter($c->get('em')));
};

$container[Integrations\ConstantContact\ConstantContactContactListController::class] = function (Container $c) {
	return new Integrations\ConstantContact\ConstantContactContactListController($c->get('em'));
};

$container[Integrations\ConstantContact\ConstantContactAuthorize::class] = function (Container $c) {
	return new Integrations\ConstantContact\ConstantContactAuthorize($c->get('em'));
};

$container[Integrations\ConstantContact\ConstantContactController::class] = function (Container $c) {
	return new App\Controllers\Integrations\ConstantContact\ConstantContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactListController::class] = function (Container $c) {
	return new App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactListController($c->get('em'));
};

$container[App\Controllers\Integrations\CampaignMonitor\CampaignMonitorSetupController::class] = function (Container $c) {
	return new App\Controllers\Integrations\CampaignMonitor\CampaignMonitorSetupController($c->get('em'));
};

$container[App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactController::class] = function (Container $c) {
	return new App\Controllers\Integrations\CampaignMonitor\CampaignMonitorContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[Integrations\RabbitMQ\CampaignMonitorWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\CampaignMonitorWorker($c->get('logger'), $c->get('em'), $c->get(Integrations\CampaignMonitor\CampaignMonitorContactController::class));
};

$container[LocationReviewErrorController::class] = function (Container $c) {
	return new LocationReviewErrorController($c->get('em'));
};

$container[NearlyImpressionController::class] = function (Container $c) {
	return new NearlyImpressionController($c->get('em'));
};

$container[SplashScreenImpressions::class] = function (Container $c) {
	return new SplashScreenImpressions($c->get('em'));
};

$container[NearlyImpressionsMasterController::class] = function (
	$c
) {
	return new NearlyImpressionsMasterController($c->get('em'));
};

$container[ConnectionLimitExceededController::class] = function (
	$c
) {
	return new ConnectionLimitExceededController($c->get('em'));
};

$container[Integrations\Mikrotik\MikrotikSymlinkController::class] = function (Container $c) {
	return new Integrations\Mikrotik\MikrotikSymlinkController($c->get('em'));
};

$container[Email::class] = function (Container $c) {
	return new Email($c->get('em'));
};

$container[NearlyStoryController::class] = function (Container $c) {
	return new NearlyStoryController($c->get('em'));
};

$container[NearlyStoryTrackingController::class] = function (Container $c) {
	return new NearlyStoryTrackingController($c->get('em'));
};

$container[Integrations\ConnectedIntegrationController::class] = function (Container $c) {
	return new Integrations\ConnectedIntegrationController($c->get('logger'), $c->get('em'));
};

$container[TopPerformingCustomersController::class] = function (
	$c
) {
	return new TopPerformingCustomersController($c->get('em'));
};

$container[Integrations\Airship\AirshipSetupController::class] = function (Container $c) {
	return new Integrations\Airship\AirshipSetupController($c->get('em'));
};

$container[AirshipContactController::class] = function (Container $c) {
	return new AirshipContactController($c->get('logger'), $c->get('em'), $c->get(FilterListController::class));
};

$container[Integrations\Airship\AirshipGroupController::class] = function (Container $c) {
	return new Integrations\Airship\AirshipGroupController($c->get('em'));
};

$container[OptInService::class] = function (Container $c) {
	return new OptInService($c->get('em'));
};

$container[Integrations\RabbitMQ\AirshipWorker::class] = function (Container $c) {
	return new Integrations\RabbitMQ\AirshipWorker(
		$c->get('logger'),
		$c->get('em'),
		$c->get(AirshipContactController::class),
		$c->get(OptInService::class)
	);
};
$container[StatsController::class]                     = function (Container $c) {
	return new StatsController($c->get('em'));
};
$container[CampaignsController::class]                 = function (Container $c) {
	$logger           = $c->get('logger');
	$entityManager    = $c->get('em');
	$emailQueueConfig = new QueueConfig(getenv('EMAIL_DELIVERY_QUEUE'));
	$smsQueueConfig   = new QueueConfig(getenv('SMS_DELIVERY_QUEUE'));
	return new CampaignsController(
		$logger,
		$entityManager,
		$userFilter = new UserFilter($c->get('em')),
		$c->get(UserProvider::class),
		$c->get(OrganizationProvider::class),
		new Queue(
			new QueueConfig(getenv('CAMPAIGN_ELIGIBILITY_QUEUE'))
		),
		new CampaignEmailSender(
			$logger,
			new BatchedQueue(
				new Queue(
					$emailQueueConfig
				),
				$emailQueueConfig
			),
			$entityManager
		),
		new CampaignSMSSender(
			$logger,
			new BatchedQueue(
				new Queue(
					$smsQueueConfig
				),
				$smsQueueConfig
			),
			$entityManager,
			$c->get(SMSTransactions::class)
		),
		new Queue(
			new QueueConfig(getenv('NOTIFICATION_QUEUE'))
		),
	);
};

$container[OrganizationProvider::class] = function (Container $container) {
	return new OrganizationProvider($container->get('em'));
};

$container[UploadController::class] = function (Container $container) {
	return new UploadController(
		$container->get('em'),
		$container->get(OrganizationProvider::class),
		$container->get(ProfileInteractionFactory::class)
	);
};

$container[EmailingProfileInteractionFactory::class] = function (Container $container) {
	$apiHost = getenv('API_HOST');
	if ($apiHost === false) {
		$apiHost = "https://api.stampede.ai";
	}
	return new EmailingProfileInteractionFactory(
		$container->get(Mail\_MailController::class),
		$container->get(LocationService::class),
		$container->get(OrganizationSettingsService::class),
		$apiHost
	);
};

$container[ExceptionMiddleware::class] = function (Container $container) {
	return new ExceptionMiddleware();
};

$container[HookNotifier::class] = function (Container $container) {
	$hooks = [
		new LoggingHook(),
		new AutoStampingHook(
			$container->get(OrganizationLoyaltyServiceFactory::class),
			$container->get('em')
		),
		new AutoReviewHook(
			$container->get(DelayedReviewSender::class)
		),
		new AutoServiceHook($container->get('em'))
	];
	if (extension_loaded('newrelic')) {
		$hooks[] = new NewRelicHook();
	}
	return new HookNotifier(
		$container->get('em'),
		$hooks,
	);
};

$container[ProfileInteractionFactory::class] = function (Container $container) {
	$pif = new ProfileInteractionFactory(
		$container->get('em'),
		new StatementExecutor($container->get('em')),
		new Queue(
			new QueueConfig(
				Integrations\SQS\QueueUrls::NOTIFICATION,
				null,
				getenv('AWS_ACCESS_KEY_ID'),
				getenv('AWS_SECRET_ACCESS_KEY')
			),
		),
		$container->get(Integrations\Hooks\_HooksController::class),
		$container->get(EmailingProfileInteractionFactory::class),
		$container->get(HookNotifier::class)
	);
	/** @var Router $eventNotifier */
	$eventNotifier = $container->get(Router::class);
	$eventNotifier->register(new InteractionNotifier($pif), LoyaltyStampCardEvent::TYPE_STAMP);
	return $pif;
};

$container[Gifting::class] = function (Container $c) {
	return new Gifting(
		$c->get(OrganizationService::class),
		$c->get('em'),
		$c->get(UserRoleChecker::class),
		$c->get(UserProvider::class)
	);
};

$container[WebTrackingController::class] = function (Container $c) {
	return new WebTrackingController(
		$c->get('em')
	);
};

$container[\App\Controllers\Redirect\Redirect::class] = function (Container $c) {
	return new App\Controllers\Redirect\Redirect(
		$c->get('em'),
		$c->get(ProfileInteractionFactory::class)
	);
};

$container[\App\Package\WebForms\Settings::class] = function (Container $c) {
	return new \App\Package\WebForms\Settings(
		$c->get('em')
	);
};

$container[\App\Package\Organisations\OrganisationProgress::class] = function (Container $c) {
	return new \App\Package\Organisations\OrganisationProgress(
		$c->get('em')
	);
};

$container[\App\Package\AppleSignIn\AppleSignIn::class] = function (Container $c) {
	return new \App\Package\AppleSignIn\AppleSignIn($c->get('em'), $c->get('oAuth'));
};

$container[MarketingController::class] = function (Container $c) {
	return new MarketingController(
		$c->get('em'),
		$c->get(OrganizationProvider::class)
	);
};

$container[VendorsController::class] = function (Container $c) {
	return new VendorsController(
		$c->get('em')
	);
};

$container[InteractionsController::class] = function (Container $c) {
	return new InteractionsController(
		$c->get('em'),
		$c->get(OrganizationProvider::class)
	);
};

$container[LocationController::class] = function (Container $c) {
	return new LocationController(
		$c->get('em')
	);
};

$container[ReportsController::class] = function (Container $c) {
	return new ReportsController(
		$c->get('em'),
		$c->get(OrganizationProvider::class),
	);
};

$container[OriginReportController::class] = function (Container $c) {
	return new OriginReportController(
		$c->get('em')
	);
};


$container[InformController::class] = function (Container $c) {
	return new InformController(
		$c->get('em')
	);
};

$container[ReviewsSettingsController::class] = function (Container $c) {
	return new ReviewsSettingsController(
		$c->get('em'),
		$c->get(OrganizationProvider::class),
		$c->get(InteractionService::class),
		$c->get(Mail\MailSender::class),
		$c->get(ReviewService::class),
		$c->get(DelayedReviewSender::class)
	);
};
$container[UserReviewController::class]      = function (Container $c) {
	return new UserReviewController(
		$c->get('em'),
		$c->get(ReviewService::class)
	);
};
$container[ReviewsReportController::class]   = function (Container $c) {
	return new ReviewsReportController(
		$c->get('em'),
		$c->get(OrganizationProvider::class),
		$c->get(ReviewService::class)
	);
};

$container[ReviewService::class]   = function (Container $c) {
	return new ReviewService(
		$c->get('em'),
		$c->get(Mail\MailSender::class)
	);
};
$container[FacebookScraper::class] = function (Container $c) {
	return new FacebookScraper(
		$c->get('em'),
		$c->get(ReviewService::class)
	);
};

$container[GoogleScraper::class]      = function (Container $c) {
	return new GoogleScraper(
		$c->get('em'),
		$c->get(ReviewService::class)
	);
};
$container[TripadvisorScraper::class] = function (Container $c) {
	return new TripadvisorScraper(
		$c->get('em'),
		$c->get(ReviewService::class)
	);
};
$container['notFoundHandler']         = function (Container $c) {
	return function (
		ServerRequestInterface $request,
		ResponseInterface $response
	) use ($c) {
		$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		$view = $c->get('view')->render(
			'Frontend/404.twig',
			[
				'page' => $actual_link,
			]
		);

		return $response->withStatus(404)->write($view);
	};
};

$container[Subscription::class] = function (Container $c) {
	return new Subscription(
		$c->get(OrganizationService::class),
		$c->get('em')
	);
};

$container[MarketingBounces::class] = function (Container $c) {
	return new MarketingBounces($c->get('em'));
};

$container[MenuController::class] = function (Container $container) {
	return new MenuController(
		$container->get('em'),
		$container->get(OrganizationProvider::class)
	);
};
