<?php

namespace App\Controllers;

use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * HealthcheckController - provides basic and detailed health check endpoints
 */
class HealthcheckController
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * HealthcheckController constructor.
     * @param Logger $logger
     * @param EntityManager $em
     */
    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Ping - simple health check
     *
     * @param Request  $request  The request
     * @param Response $response The response
     *
     * @return Response
     */
    public function ping(Request $request, Response $response): Response
    {
        return $response->withStatus(200)->withJson([
            'message' => 'Version: ' . getenv('APP_VERSION')
        ], 200);
    }

    /**
     * Detailed health check - checks connections to database and Redis
     *
     * @param Request  $request  The request
     * @param Response $response The response
     *
     * @return Response
     */
    public function getStatus(Request $request, Response $response): Response
    {
        $databases = [
            "backendDb" => $this->em
        ];
        $caches = [
            "infrastructureCache" => getenv('INFRASTRUCTURE_REDIS'),
            "nearlyCache" => getenv('NEARLY_REDIS'),
            "marketingCache" => getenv('MARKETING_REDIS'),
            "connectCache" => getenv('CONNECT_REDIS')
        ];
        $success = true;
        $results = [];
        foreach ($databases as $name => $db) {
            try {
                $stmt = $db->getConnection()->prepare("select 1+1");
                $stmt->execute();
                $stmt->fetchAll();
                $results[$name] = 'OK';
            } catch (\Exception $e) {
                $results[$name] = $e->getMessage();
                $success        = false;
            }
        }
        foreach ($caches as $name => $cacheUrl) {
            try {
                $cache = new CacheEngine($cacheUrl, 1);
                $results[$name] = $cache->getStats();
            } catch (\Exception $e) {
                $results[$name] = $e->getMessage();
                $success        = false;
            }
        }
        return $response->withStatus($success ? 200 : 500)->withJson($results);
    }
}
