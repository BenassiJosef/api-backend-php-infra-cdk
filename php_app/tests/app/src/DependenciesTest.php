<?php
declare(strict_types=1);

namespace StampedeTests\app\src;

use App\Controllers\Integrations\Xero\_XeroController;
use App\Package\Auth\Controller\ExternalServiceController;
use Doctrine\ORM\EntityManager;
use Exception;
use Monolog\Logger;
use OAuth2\Server;
use PHPUnit\Framework\TestCase;
use Slim\App;
use App\Controllers\Integrations\OpenMesh\OpenMeshNearlySettings;
use Slim\Route;


class DummyRouter
{

}

final class DependenciesTest extends TestCase
{

    public function testAllDependencies(): void
    {
        // Instantiate the application
        $settings = [
            'cancellationEmails' => 'bob@hello.com',
            'template'           => '/src/Templates/',
            'chargebee'          => [
                'enabled' => false
            ],
            'PoweredBy'          => 'BlackBx',
            "outputBuffering"    => false
        ];
        $app      = new App($settings);
        // Set up dependencies
        require __DIR__ . '/../../../app/dependencies.php';

        $container             = $app->getContainer();
        $container['settings'] = $settings;
        // stub out some items that can't be created in the test environment
        $container['em']     = $this->createMock(EntityManager::class);
        $container['logger'] = $this->createMock(Logger::class);
        $container['oAuth']  = $this->createMock(Server::class);

        // set some required environment vars
        putenv('GC_ACCESS_TOKEN=123');

        $blacklist = [
            'response',
            'phpErrorHandler',
            'firebase',
            'Firebase\Database',
            'mysql',
            'pdo',
            _XeroController::class,
            OpenMeshNearlySettings::class,
            ExternalServiceController::class,
        ];

        $keys = $container->keys();
        foreach ($keys as $key) {
            if (!in_array($key, $blacklist)) {
                $this->assertNotNull($container->get($key));
            }
        }

        // setup all the routes
        $blacklistClasses = ['App\Controllers\Billing\Subscriptions\_HostedPages', 'App\Controllers\Integrations\SNS\_QueueController'];
        require __DIR__ . '/../../../app/routes.php';
        $routes = $app->getContainer()->router->getRoutes();
        $failed = false;
        /** @var Route $routes */
        foreach ($routes as $route) {
            /** @var Route $route */
            $pattern  = $route->getPattern();
            $callable = explode(":", $route->getCallable());
            if (count($callable) === 1) {
                continue;
            }
            $className = $callable[0];
            if (!in_array($className, $blacklistClasses)) {
                // try and get the class from the dependencies
                try {
                    $method = $callable[1];
                    $object = $container->get($className);
                    $this->assertNotNull($object, "Could not resolve class '$className' for route '$pattern'");
                    // make sure the method is implemented
                    $this->assertTrue(method_exists($object, $method), "Could not find method '$method' on class '$className' for route '$pattern'");
                } catch (Exception $ex) {
                    print("{$ex->getMessage()} for class ${className} on {$pattern}\n");
                    $failed = true;
                }
            }
        }
        self::assertFalse($failed, "Something went wrong");
    }
}
