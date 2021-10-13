<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/08/2017
 * Time: 19:38
 */

namespace App\Utils;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class RadiusEngine
{

    private $model;
    private static $instance = null;

    private function __construct()
    {
        $settings = [
            'driver'        => 'pdo_mysql',
            'host'          => getenv('doctrine_connection_host'),
            'dbname'        => 'radius',
            'user'          => getenv('doctrine_connection_user'),
            'password'      => getenv('doctrine_connection_password'),
            'charset'       => 'utf8',
            'driverOptions' => [
                'x_reconnect_attempts' => 10
            ]
        ];

        $config      = Setup::createAnnotationMetadataConfiguration(
            ['app/src/Models'],
            true,
            __DIR__ . '/../cache/proxies',
            null,
            false
        );
        $this->model = EntityManager::create($settings, $config);

        return $this->model;
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new RadiusEngine();
        }

        return self::$instance;
    }

    public function persist($em)
    {
        return $this->model->persist($em);
    }

    public function getRepository($em)
    {
        return $this->model->getRepository($em);
    }

    public function flush()
    {
        return $this->model->flush();
    }

    public function createQueryBuilder($em)
    {
        return $this->model->createQueryBuilder();
    }
}
